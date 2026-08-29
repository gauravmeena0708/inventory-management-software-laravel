<?php

namespace App\Enums;

enum VerificationType: string
{
    case ANNUAL = 'annual';
    case SURPRISE = 'surprise';
    case AD_HOC = 'ad_hoc';
    case HANDOVER = 'handover';
    case PERIODIC = 'periodic';

    public function label(): string
    {
        return match ($this) {
            self::ANNUAL => 'Annual Physical Verification',
            self::SURPRISE => 'Surprise Physical Check',
            self::AD_HOC => 'Ad-Hoc Stock Verification',
            self::HANDOVER => 'Charge Handover Verification',
            self::PERIODIC => 'Periodic Verification',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
