<?php

namespace App\Enums;

enum AcquisitionType: string
{
    case GEM = 'gem';
    case DIRECT_PURCHASE = 'direct_purchase';
    case TENDER = 'tender';
    case DONATION = 'donation';
    case TRANSFER = 'transfer';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GEM => 'Government e-Marketplace (GeM)',
            self::DIRECT_PURCHASE => 'Direct Departmental Purchase',
            self::TENDER => 'Open Tender / RFP',
            self::DONATION => 'Grant / Donation',
            self::TRANSFER => 'Inter-Departmental Transfer Receipt',
            self::OTHER => 'Other Acquisition',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
