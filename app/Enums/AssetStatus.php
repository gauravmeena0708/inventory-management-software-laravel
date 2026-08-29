<?php

namespace App\Enums;

enum AssetStatus: string
{
    case IN_STOCK = 'in_stock';
    case RESERVED = 'reserved';
    case IN_USE = 'in_use';
    case IN_TRANSIT = 'in_transit';
    case UNDER_MAINTENANCE = 'under_maintenance';
    case MISSING = 'missing';
    case PENDING_DISPOSAL = 'pending_disposal';
    case DECOMMISSIONED = 'decommissioned';
    case DISPOSED = 'disposed';

    /**
     * Get the human-readable label for the asset status.
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_STOCK => 'In Stock',
            self::RESERVED => 'Reserved',
            self::IN_USE => 'In Use',
            self::IN_TRANSIT => 'In Transit',
            self::UNDER_MAINTENANCE => 'Under Maintenance',
            self::MISSING => 'Missing',
            self::PENDING_DISPOSAL => 'Pending Disposal',
            self::DECOMMISSIONED => 'Decommissioned',
            self::DISPOSED => 'Disposed',
        };
    }

    /**
     * Get the UI color/badge styling token for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::IN_STOCK => 'green',
            self::RESERVED => 'purple',
            self::IN_USE => 'blue',
            self::IN_TRANSIT => 'sky',
            self::UNDER_MAINTENANCE => 'amber',
            self::MISSING => 'red',
            self::PENDING_DISPOSAL => 'orange',
            self::DECOMMISSIONED => 'zinc',
            self::DISPOSED => 'gray',
        };
    }

    /**
     * Get an array of all asset status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get an array of value => label pairs.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
