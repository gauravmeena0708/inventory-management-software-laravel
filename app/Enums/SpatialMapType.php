<?php

namespace App\Enums;

enum SpatialMapType: string
{
    case IMAGE = 'image';
    case SVG = 'svg';
    case GEOJSON = 'geojson';
    case THREE_D_MODEL = '3d_model';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
