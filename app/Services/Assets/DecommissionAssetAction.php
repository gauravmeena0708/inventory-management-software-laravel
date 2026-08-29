<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class DecommissionAssetAction
{
    /**
     * Decommission an asset, closing active assignments and updating status.
     */
    public function execute(
        Asset $asset,
        ?User $actor = null,
        ?string $reason = null,
        DateTimeInterface|string|null $decommissionedAt = null
    ): Asset {
        return DB::transaction(function () use (
            $asset,
            $actor,
            $reason,
            $decommissionedAt
        ) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            $timestamp = $decommissionedAt
                ? ($decommissionedAt instanceof DateTimeInterface ? $decommissionedAt : now()->parse($decommissionedAt))
                : now();

            // Close any open assignment
            $openAssignments = AssetAssignment::where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->get();

            foreach ($openAssignments as $openAssignment) {
                $openAssignment->update([
                    'returned_at' => $timestamp,
                    'return_recorded_by' => $actor?->id,
                    'remarks' => trim(($openAssignment->remarks ? $openAssignment->remarks . "\n" : '') . '[Closed on decommissioning]'),
                ]);
            }

            $updatedRemarks = $lockedAsset->remarks;
            if ($reason) {
                $updatedRemarks = trim(($updatedRemarks ? $updatedRemarks . "\n" : '') . "[Decommissioned]: {$reason}");
            }

            $lockedAsset->update([
                'assigned_official_id' => null,
                'status' => AssetStatus::DECOMMISSIONED,
                'remarks' => $updatedRemarks,
            ]);

            return $lockedAsset->fresh();
        });
    }
}
