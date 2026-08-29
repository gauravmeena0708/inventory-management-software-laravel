<?php

namespace App\Enums;

enum AuditType: string
{
    case INTERNAL_AUDIT = 'internal_audit';
    case INVENTORY_AUDIT = 'inventory_audit';
    case PHYSICAL_VERIFICATION_AUDIT = 'physical_verification_audit';
    case IT_ASSET_REVIEW = 'it_asset_review';
    case SPECIAL_AUDIT = 'special_audit';
    case INSPECTION = 'inspection';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL_AUDIT => 'Internal Audit Inspection',
            self::INVENTORY_AUDIT => 'Statutory Inventory Audit',
            self::PHYSICAL_VERIFICATION_AUDIT => 'Physical Verification Audit',
            self::IT_ASSET_REVIEW => 'IT Asset & Infrastructure Review',
            self::SPECIAL_AUDIT => 'Special Compliance Audit',
            self::INSPECTION => 'Departmental Field Inspection',
            self::OTHER => 'Other Audit',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
