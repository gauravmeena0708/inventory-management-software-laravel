<?php

namespace App\Enums;

enum AssetStatus: string
{
    case IN_USE = 'in_use';
    case IN_STOCK = 'in_stock';
    case UNDER_MAINTENANCE = 'under_maintenance';
    case DECOMMISSIONED = 'decommissioned';

    /**
     * Get the human-readable label for the asset status.
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_USE => 'In Use',
            self::IN_STOCK => 'In Stock',
            self::UNDER_MAINTENANCE => 'Under Maintenance',
            self::DECOMMISSIONED => 'Decommissioned',
        };
    }

    /**
     * Get the UI color/badge styling token for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::IN_USE => 'blue',
            self::IN_STOCK => 'green',
            self::UNDER_MAINTENANCE => 'amber',
            self::DECOMMISSIONED => 'red',
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
        return [
            self::IN_USE->value => self::IN_USE->label(),
            self::IN_STOCK->value => self::IN_STOCK->label(),
            self::UNDER_MAINTENANCE->value => self::UNDER_MAINTENANCE->label(),
            self::DECOMMISSIONED->value => self::DECOMMISSIONED->label(),
        ];
    }
}
