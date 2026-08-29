<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case AWAITING_VENDOR = 'awaiting_vendor';
    case UNDER_REPAIR = 'under_repair';
    case AWAITING_PART = 'awaiting_part';
    case RESOLVED = 'resolved';
    case RETURNED = 'returned';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::AWAITING_VENDOR => 'Awaiting Vendor',
            self::UNDER_REPAIR => 'Under Repair',
            self::AWAITING_PART => 'Awaiting Parts',
            self::RESOLVED => 'Resolved',
            self::RETURNED => 'Returned',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'amber',
            self::ACKNOWLEDGED => 'sky',
            self::AWAITING_VENDOR, self::AWAITING_PART => 'purple',
            self::UNDER_REPAIR => 'orange',
            self::RESOLVED => 'teal',
            self::RETURNED => 'blue',
            self::CLOSED => 'green',
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
