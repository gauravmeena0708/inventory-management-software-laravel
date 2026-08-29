<?php

namespace App\Enums;

enum DisposalMethod: string
{
    case E_WASTE = 'e_waste';
    case AUCTION = 'auction';
    case TRANSFER = 'transfer';
    case SCRAP = 'scrap';
    case DESTRUCTION = 'destruction';
    case RETURN_TO_VENDOR = 'return_to_vendor';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::E_WASTE => 'E-Waste Recycling',
            self::AUCTION => 'Public Auction',
            self::TRANSFER => 'Inter-Departmental Transfer',
            self::SCRAP => 'Scrap Sale',
            self::DESTRUCTION => 'Certified Physical Destruction',
            self::RETURN_TO_VENDOR => 'Return to Vendor / Buyback',
            self::OTHER => 'Other Method',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
