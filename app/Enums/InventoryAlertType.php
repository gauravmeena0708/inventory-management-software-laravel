<?php

namespace App\Enums;

enum InventoryAlertType: string
{
    case WARRANTY_EXPIRING = 'warranty_expiring';
    case AMC_EXPIRING = 'amc_expiring';
    case END_OF_SUPPORT = 'end_of_support';
    case TRANSFER_PENDING_RECEIPT = 'transfer_pending_receipt';
    case MAINTENANCE_OVERDUE = 'maintenance_overdue';
    case VERIFICATION_OVERDUE = 'verification_overdue';
    case MISSING_ASSET = 'missing_asset';
    case LOW_STOCK = 'low_stock';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WARRANTY_EXPIRING => 'Warranty Expiring Soon',
            self::AMC_EXPIRING => 'AMC Contract Expiring Soon',
            self::END_OF_SUPPORT => 'End-of-Support / EoL Reached',
            self::TRANSFER_PENDING_RECEIPT => 'Transfer Pending Receipt',
            self::MAINTENANCE_OVERDUE => 'Maintenance Ticket SLA Exceeded',
            self::VERIFICATION_OVERDUE => 'Annual Physical Verification Overdue',
            self::MISSING_ASSET => 'Asset Flagged as Missing in Verification',
            self::LOW_STOCK => 'Low Stock Threshold Reached',
            self::OTHER => 'Operational Alert',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
