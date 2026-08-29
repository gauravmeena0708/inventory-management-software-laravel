<?php

namespace App\Enums;

enum DisposalStatus: string
{
    case RECOMMENDED = 'recommended';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case PENDING_DISPOSAL = 'pending_disposal';
    case DISPOSED = 'disposed';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::RECOMMENDED => 'Recommended',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::PENDING_DISPOSAL => 'Pending Disposal',
            self::DISPOSED => 'Disposed',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RECOMMENDED => 'amber',
            self::UNDER_REVIEW => 'purple',
            self::APPROVED => 'blue',
            self::PENDING_DISPOSAL => 'orange',
            self::DISPOSED => 'gray',
            self::REJECTED => 'red',
            self::CANCELLED => 'zinc',
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
