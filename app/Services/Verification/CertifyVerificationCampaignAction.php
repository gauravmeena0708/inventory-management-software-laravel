<?php

namespace App\Services\Verification;

use App\Enums\VerificationResult;
use App\Enums\VerificationStatus;
use App\Models\InventoryVerification;
use App\Models\InventoryVerificationItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CertifyVerificationCampaignAction
{
    /**
     * Certify and finalize the physical verification campaign.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(InventoryVerification $campaign, array $options, User $certifier): InventoryVerification
    {
        return DB::transaction(function () use ($campaign, $options, $certifier) {
            /** @var InventoryVerification $lockedCampaign */
            $lockedCampaign = InventoryVerification::where('id', $campaign->id)->lockForUpdate()->firstOrFail();

            if ($lockedCampaign->status === VerificationStatus::CERTIFIED) {
                throw ValidationException::withMessages([
                    'status' => 'Verification campaign is already certified.',
                ]);
            }

            // Identify any missing unverified assets from snapshot population
            $expectedAssetIds = $lockedCampaign->snapshot_population['asset_ids'] ?? [];
            $scannedAssetIds = $lockedCampaign->items()->whereNotNull('asset_id')->pluck('asset_id')->all();

            $unscannedAssetIds = array_diff($expectedAssetIds, $scannedAssetIds);

            // Record NOT_FOUND items for unscanned assets
            foreach ($unscannedAssetIds as $unscannedId) {
                InventoryVerificationItem::create([
                    'inventory_verification_id' => $lockedCampaign->id,
                    'asset_id' => $unscannedId,
                    'result' => VerificationResult::NOT_FOUND,
                    'verified_by' => $certifier->id,
                    'verified_at' => now(),
                    'remarks' => 'Auto-flagged as NOT_FOUND during final campaign certification.',
                    'is_reconciled' => false,
                ]);
            }

            $lockedCampaign->update([
                'status' => VerificationStatus::CERTIFIED,
                'completed_at' => $lockedCampaign->completed_at ?? now(),
                'certified_at' => now(),
                'approved_by' => $certifier->id,
                'remarks' => trim(($lockedCampaign->remarks ? $lockedCampaign->remarks . "\n" : '') . '[Certification Note]: ' . ($options['remarks'] ?? 'Certified without exceptions.')),
            ]);

            return $lockedCampaign->fresh();
        });
    }
}
