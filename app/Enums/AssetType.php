<?php

namespace App\Enums;

enum AssetType: string
{
    case DESKTOP = 'desktop';
    case LAPTOP = 'laptop';
    case SERVER = 'server';
    case SWITCH = 'switch';
    case STORAGE = 'storage';
    case OTHER = 'other';

    /**
     * Get the human-readable label for the asset type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DESKTOP => 'Desktop',
            self::LAPTOP => 'Laptop',
            self::SERVER => 'Server',
            self::SWITCH => 'Switch',
            self::STORAGE => 'Storage',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get an array of all asset type values.
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
            self::DESKTOP->value => self::DESKTOP->label(),
            self::LAPTOP->value => self::LAPTOP->label(),
            self::SERVER->value => self::SERVER->label(),
            self::SWITCH->value => self::SWITCH->label(),
            self::STORAGE->value => self::STORAGE->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }
}
