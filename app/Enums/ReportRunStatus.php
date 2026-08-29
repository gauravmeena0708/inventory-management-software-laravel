<?php

namespace App\Enums;

enum ReportRunStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case APPROVED = 'approved';
    case FINAL = 'final';
    case SUPERSEDED = 'superseded';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::GENERATED => 'Generated',
            self::SUBMITTED => 'Submitted for Verification',
            self::VERIFIED => 'Verified',
            self::APPROVED => 'Approved',
            self::FINAL => 'Certified / Final',
            self::SUPERSEDED => 'Superseded by Newer Version',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::FINAL, self::SUPERSEDED]);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::GENERATED => 'sky',
            self::SUBMITTED => 'amber',
            self::VERIFIED => 'blue',
            self::APPROVED => 'indigo',
            self::FINAL => 'green',
            self::SUPERSEDED => 'purple',
            self::CANCELLED => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
