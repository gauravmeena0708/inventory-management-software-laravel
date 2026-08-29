<?php

namespace App\Services\Verification;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Models\Asset;
use App\Models\InventoryVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateVerificationCampaignAction
{
    /**
     * Create a verification campaign and snapshot the expected asset population.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $creator): InventoryVerification
    {
        return DB::transaction(function () use ($data, $creator) {
            $unitId = (int) $data['organizational_unit_id'];
            $siteId = isset($data['site_id']) ? (int) $data['site_id'] : null;

            // Query expected assets for this unit / site
            $assetQuery = Asset::query()
                ->where('organizational_unit_id', $unitId)
                ->whereNotIn('status', ['disposed', 'decommissioned']);

            if ($siteId) {
                $assetQuery->whereHas('location', fn ($q) => $q->where('site_id', $siteId));
            }

            $expectedAssetIds = $assetQuery->pluck('id')->all();

            $campaign = InventoryVerification::create([
                'organizational_unit_id' => $unitId,
                'site_id' => $siteId,
                'name' => $data['name'],
                'financial_year' => $data['financial_year'] ?? date('Y') . '-' . (date('Y') + 1),
                'verification_type' => $data['verification_type'] ?? VerificationType::ANNUAL,
                'planned_from' => $data['planned_from'] ?? now()->toDateString(),
                'planned_to' => $data['planned_to'] ?? now()->addMonth()->toDateString(),
                'started_at' => now(),
                'status' => VerificationStatus::IN_PROGRESS,
                'created_by' => $creator->id,
                'remarks' => $data['remarks'] ?? null,
                'snapshot_population' => [
                    'asset_count' => count($expectedAssetIds),
                    'asset_ids' => $expectedAssetIds,
                    'snapshotted_at' => now()->toIso8601String(),
                ],
            ]);

            return $campaign;
        });
    }
}
