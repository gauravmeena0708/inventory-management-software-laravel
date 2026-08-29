<?php

namespace App\Enums;

enum ReportCategory: string
{
    case REGISTER = 'REGISTER';
    case MIS = 'MIS';
    case PERIODIC = 'PERIODIC';
    case VERIFICATION = 'VERIFICATION';
    case AUDIT = 'AUDIT';

    public function label(): string
    {
        return match ($this) {
            self::REGISTER => 'Official Inventory Register',
            self::MIS => 'Management Information Report',
            self::PERIODIC => 'Periodic Return / Statement',
            self::VERIFICATION => 'Verification Statement',
            self::AUDIT => 'Audit & Compliance Report',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
