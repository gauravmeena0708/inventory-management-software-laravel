<?php

namespace App\Enums;

enum AssetRelationshipType: string
{
    case COMPONENT_OF = 'component_of';
    case ACCESSORY_OF = 'accessory_of';
    case INSTALLED_IN = 'installed_in';
    case CONNECTED_TO = 'connected_to';
    case BACKED_UP_BY = 'backed_up_by';
    case POWERED_BY = 'powered_by';
    case PART_OF_BUNDLE = 'part_of_bundle';
    case REPLACED_BY = 'replaced_by';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::COMPONENT_OF => 'Component Of',
            self::ACCESSORY_OF => 'Accessory Of',
            self::INSTALLED_IN => 'Installed In (Rack/Slot)',
            self::CONNECTED_TO => 'Connected To',
            self::BACKED_UP_BY => 'Backed Up By',
            self::POWERED_BY => 'Powered By (UPS/PDU)',
            self::PART_OF_BUNDLE => 'Part of Bundle',
            self::REPLACED_BY => 'Replaced By',
            self::OTHER => 'Other Relation',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
