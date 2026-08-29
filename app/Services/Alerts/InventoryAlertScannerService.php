<?php

namespace App\Services\Alerts;

use App\Enums\InventoryAlertType;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\InventoryAlert;
use App\Models\InventoryVerificationItem;
use App\Models\MaintenanceTicket;
use App\Models\OrganizationalUnit;
use Illuminate\Support\Facades\DB;

class InventoryAlertScannerService
{
    /**
     * Scan an organizational unit and generate or update active inventory alerts.
     *
     * @return int Number of alerts generated or updated.
     */
    public function scanUnit(OrganizationalUnit $unit): int
    {
        return DB::transaction(function () use ($unit) {
            $count = 0;
            $now = now();

            // 1. Assets with warranty expiring within 60 days
            $expiringWarrantyAssets = Asset::query()
                ->where('organizational_unit_id', $unit->id)
                ->whereNotNull('warranty_expiry')
                ->whereBetween('warranty_expiry', [$now->toDateString(), $now->copy()->addDays(60)->toDateString()])
                ->whereNotIn('status', ['disposed', 'decommissioned'])
                ->get();

            foreach ($expiringWarrantyAssets as $asset) {
                InventoryAlert::updateOrCreate([
                    'organizational_unit_id' => $unit->id,
                    'asset_id' => $asset->id,
                    'alert_type' => InventoryAlertType::WARRANTY_EXPIRING->value,
                ], [
                    'severity' => 'warning',
                    'title' => "Warranty Expiring: {$asset->name} ({$asset->asset_tag})",
                    'message' => "OEM warranty expires on {$asset->warranty_expiry->format('d M Y')}.",
                    'due_date' => $asset->warranty_expiry,
                ]);
                $count++;
            }

            // 2. Agreements expiring within 60 days
            $expiringAgreements = Agreement::query()
                ->where('organizational_unit_id', $unit->id)
                ->whereNotNull('expiry')
                ->whereBetween('expiry', [$now->toDateString(), $now->copy()->addDays(60)->toDateString()])
                ->get();

            foreach ($expiringAgreements as $agreement) {
                InventoryAlert::updateOrCreate([
                    'organizational_unit_id' => $unit->id,
                    'agreement_id' => $agreement->id,
                    'alert_type' => InventoryAlertType::AMC_EXPIRING->value,
                ], [
                    'severity' => 'warning',
                    'title' => "Agreement Expiring: {$agreement->name}",
                    'message' => "Contract expires on {$agreement->expiry->format('d M Y')}. Renewal or renegotiation required.",
                    'due_date' => $agreement->expiry,
                ]);
                $count++;
            }

            // 3. Pending transfers awaiting receipt for this unit
            $incomingTransfers = AssetTransfer::query()
                ->where('to_organizational_unit_id', $unit->id)
                ->where('status', 'in_transit')
                ->get();

            foreach ($incomingTransfers as $transfer) {
                InventoryAlert::updateOrCreate([
                    'organizational_unit_id' => $unit->id,
                    'asset_id' => $transfer->asset_id,
                    'alert_type' => InventoryAlertType::TRANSFER_PENDING_RECEIPT->value,
                ], [
                    'severity' => 'info',
                    'title' => "Transfer In-Transit: Transfer #{$transfer->transfer_number}",
                    'message' => "Asset has been dispatched from unit #{$transfer->from_organizational_unit_id} and is awaiting receipt verification.",
                    'due_date' => $transfer->dispatched_at?->toDateString(),
                ]);
                $count++;
            }

            // 4. Missing assets from physical verifications
            $missingItems = InventoryVerificationItem::query()
                ->where('expected_organizational_unit_id', $unit->id)
                ->where('result', 'not_found')
                ->where('is_reconciled', false)
                ->get();

            foreach ($missingItems as $item) {
                if ($item->asset_id) {
                    InventoryAlert::updateOrCreate([
                        'organizational_unit_id' => $unit->id,
                        'asset_id' => $item->asset_id,
                        'alert_type' => InventoryAlertType::MISSING_ASSET->value,
                    ], [
                        'severity' => 'critical',
                        'title' => "Missing Asset Flagged in Verification",
                        'message' => "Asset #{$item->asset_id} was recorded as NOT_FOUND in verification campaign #{$item->inventory_verification_id}.",
                        'due_date' => $item->verified_at?->toDateString(),
                    ]);
                    $count++;
                }
            }

            return $count;
        });
    }
}
