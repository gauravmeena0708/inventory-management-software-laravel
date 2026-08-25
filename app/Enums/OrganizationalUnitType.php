<?php

namespace App\Enums;

enum OrganizationalUnitType: string
{
    case ROOT = 'ROOT';
    case HEAD_OFFICE = 'HEAD_OFFICE';
    case NDC = 'NDC';
    case ZONAL_OFFICE = 'ZONAL_OFFICE';
    case REGIONAL_OFFICE = 'REGIONAL_OFFICE';
    case DISTRICT_OFFICE = 'DISTRICT_OFFICE';
    case SPECIAL_STATE_OFFICE = 'SPECIAL_STATE_OFFICE';
    case VIGILANCE_HQ = 'VIGILANCE_HQ';
    case VIGILANCE_ZVD = 'VIGILANCE_ZVD';
    case PDUNASS = 'PDUNASS';
    case ZTI = 'ZTI';
}
