<?php

namespace App\Enums;

enum AgreementCoverageType: string
{
    case WARRANTY = 'warranty';
    case AMC = 'amc';
    case COMPREHENSIVE_AMC = 'comprehensive_amc';
    case SUPPORT = 'support';
    case LICENSE = 'license';
    case CLOUD_SERVICE = 'cloud_service';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WARRANTY => 'OEM Warranty',
            self::AMC => 'Annual Maintenance Contract (AMC)',
            self::COMPREHENSIVE_AMC => 'Comprehensive AMC (Parts + Labour)',
            self::SUPPORT => 'Technical Support',
            self::LICENSE => 'Software License Subscription',
            self::CLOUD_SERVICE => 'Cloud Service / SaaS',
            self::OTHER => 'Other Contractual Coverage',
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
