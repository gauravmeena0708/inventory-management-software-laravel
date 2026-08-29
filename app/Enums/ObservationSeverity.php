<?php

namespace App\Enums;

enum ObservationSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low Risk',
            self::MEDIUM => 'Medium Risk',
            self::HIGH => 'High Risk',
            self::CRITICAL => 'Critical Irregularity',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'zinc',
            self::MEDIUM => 'blue',
            self::HIGH => 'amber',
            self::CRITICAL => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
