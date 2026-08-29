<?php

namespace App\Services\Verification;

use App\Enums\LifecycleEventType;
use App\Enums\VerificationResult;
use App\Models\Asset;
use App\Models\InventoryVerification;
use App\Models\InventoryVerificationItem;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class RecordVerificationScanAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Record the physical scan of an asset against a verification campaign.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(InventoryVerification $campaign, Asset $asset, array $data, User $verifier): InventoryVerificationItem
    {
        return DB::transaction(function () use ($campaign, $asset, $data, $verifier) {
            $expectedUnitId = $asset->organizational_unit_id;
            $expectedLocationId = $asset->location_id;
            $expectedOfficialId = $asset->assigned_official_id;

            $observedUnitId = $data['observed_organizational_unit_id'] ?? $expectedUnitId;
            $observedLocationId = $data['observed_location_id'] ?? $expectedLocationId;
            $observedOfficialId = $data['observed_official_id'] ?? $expectedOfficialId;

            // Determine verification result
            $result = $data['result'] ?? null;
            if (! $result) {
                if ($observedLocationId != $expectedLocationId) {
                    $result = VerificationResult::WRONG_LOCATION;
                } elseif ($observedOfficialId != $expectedOfficialId) {
                    $result = VerificationResult::WRONG_CUSTODIAN;
                } elseif ($observedUnitId != $expectedUnitId) {
                    $result = VerificationResult::WRONG_LOCATION;
                } else {
                    $result = VerificationResult::VERIFIED;
                }
            } elseif (is_string($result)) {
                $result = VerificationResult::from($result);
            }

            $item = InventoryVerificationItem::create([
                'inventory_verification_id' => $campaign->id,
                'asset_id' => $asset->id,
                'expected_organizational_unit_id' => $expectedUnitId,
                'observed_organizational_unit_id' => $observedUnitId,
                'expected_location_id' => $expectedLocationId,
                'observed_location_id' => $observedLocationId,
                'expected_official_id' => $expectedOfficialId,
                'observed_official_id' => $observedOfficialId,
                'result' => $result,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'remarks' => $data['remarks'] ?? null,
                'photo_attachment_id' => $data['photo_attachment_id'] ?? null,
                'is_reconciled' => $result === VerificationResult::VERIFIED,
            ]);

            // Emit lifecycle event
            $eventType = $result->isDiscrepancy()
                ? LifecycleEventType::VERIFICATION_EXCEPTION
                : LifecycleEventType::VERIFIED;

            $this->recordLifecycleEvent->execute(
                $asset,
                $eventType,
                $verifier,
                [
                    'reference_type' => 'InventoryVerification',
                    'reference_id' => $campaign->id,
                    'reference_number' => $campaign->name,
                    'remarks' => "Physical verification: {$result->label()}." . ($item->remarks ? " Note: {$item->remarks}" : ''),
                    'metadata' => [
                        'campaign_id' => $campaign->id,
                        'verification_item_id' => $item->id,
                        'result' => $result->value,
                    ],
                ]
            );

            return $item;
        });
    }
}
