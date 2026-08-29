<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class ReturnAssetAction
{
    /**
     * Record the return of an asset into stock and close its active assignment.
     */
    public function execute(
        Asset $asset,
        ?User $actor = null,
        ?string $remarks = null,
        ?string $conditionIn = null,
        DateTimeInterface|string|null $returnedAt = null
    ): ?AssetAssignment {
        return DB::transaction(function () use (
            $asset,
            $actor,
            $remarks,
            $conditionIn,
            $returnedAt
        ) {
            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            $timestamp = $returnedAt
                ? ($returnedAt instanceof DateTimeInterface ? $returnedAt : now()->parse($returnedAt))
                : now();

            /** @var AssetAssignment|null $openAssignment */
            $openAssignment = AssetAssignment::where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->latest('assigned_at')
                ->first();

            if ($openAssignment) {
                $mergedRemarks = $openAssignment->remarks;
                if ($remarks) {
                    $mergedRemarks = trim(($mergedRemarks ? $mergedRemarks . "\n" : '') . "[Return Note]: {$remarks}");
                }

                $openAssignment->update([
                    'returned_at' => $timestamp,
                    'return_recorded_by' => $actor?->id,
                    'condition_in' => $conditionIn ?? $openAssignment->condition_in,
                    'remarks' => $mergedRemarks,
                ]);
            }

            $lockedAsset->update([
                'assigned_official_id' => null,
                'status' => AssetStatus::IN_STOCK,
            ]);

            return $openAssignment;
        });
    }
}
