<?php

namespace App\Services\Verification;

use App\Enums\LifecycleEventType;
use App\Enums\VerificationResult;
use App\Models\Asset;
use App\Models\InventoryVerificationItem;
use App\Models\User;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;

class ReconcileVerificationDiscrepancyAction
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Reconcile a verification discrepancy by updating asset state and closing discrepancy.
     *
     * @param  array<string, mixed>  $options
     */
    public function execute(InventoryVerificationItem $item, array $options, User $actor): InventoryVerificationItem
    {
        return DB::transaction(function () use ($item, $options, $actor) {
            /** @var InventoryVerificationItem $lockedItem */
            $lockedItem = InventoryVerificationItem::where('id', $item->id)->lockForUpdate()->firstOrFail();

            if (! $lockedItem->asset_id) {
                $lockedItem->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now(),
                    'reconciled_by' => $actor->id,
                    'remarks' => trim(($lockedItem->remarks ? $lockedItem->remarks . "\n" : '') . '[Reconciliation]: ' . ($options['remarks'] ?? 'Resolved')),
                ]);

                return $lockedItem->fresh();
            }

            /** @var Asset $lockedAsset */
            $lockedAsset = Asset::where('id', $lockedItem->asset_id)->lockForUpdate()->firstOrFail();

            $applyLocation = $options['apply_observed_location'] ?? true;
            $applyCustodian = $options['apply_observed_custodian'] ?? true;

            $changes = [];

            if ($applyLocation && $lockedItem->observed_location_id && $lockedItem->observed_location_id !== $lockedAsset->location_id) {
                $oldLocation = $lockedAsset->location_id;
                $lockedAsset->update(['location_id' => $lockedItem->observed_location_id]);
                $changes['location_id'] = ['from' => $oldLocation, 'to' => $lockedItem->observed_location_id];
            }

            if ($applyCustodian && $lockedItem->observed_official_id !== $lockedAsset->assigned_official_id) {
                $oldOfficial = $lockedAsset->assigned_official_id;
                $lockedAsset->update(['assigned_official_id' => $lockedItem->observed_official_id]);
                $changes['assigned_official_id'] = ['from' => $oldOfficial, 'to' => $lockedItem->observed_official_id];
            }

            $lockedItem->update([
                'is_reconciled' => true,
                'reconciled_at' => now(),
                'reconciled_by' => $actor->id,
                'remarks' => trim(($lockedItem->remarks ? $lockedItem->remarks . "\n" : '') . '[Reconciliation Note]: ' . ($options['remarks'] ?? 'Discrepancy reconciled.')),
            ]);

            // Emit DATA_CORRECTED lifecycle event
            $this->recordLifecycleEvent->execute(
                $lockedAsset,
                LifecycleEventType::DATA_CORRECTED,
                $actor,
                [
                    'reference_type' => 'InventoryVerificationItem',
                    'reference_id' => $lockedItem->id,
                    'remarks' => "Reconciled discrepancy from verification campaign #{$lockedItem->inventory_verification_id}: " . ($options['remarks'] ?? 'Updated to observed values.'),
                    'metadata' => [
                        'verification_item_id' => $lockedItem->id,
                        'changes' => $changes,
                    ],
                ]
            );

            return $lockedItem->fresh();
        });
    }
}
