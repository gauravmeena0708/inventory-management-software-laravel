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
    case INTERNAL_AUDIT_WING = 'INTERNAL_AUDIT_WING';
    case PDUNASS = 'PDUNASS';
    case ZTI = 'ZTI';
    case HOLIDAY_HOME = 'HOLIDAY_HOME';
    case ALTERNATE_DATA_CENTRE = 'ALTERNATE_DATA_CENTRE';
    case DIRECTORATE = 'DIRECTORATE';
    case DIVISION = 'DIVISION';
    case BRANCH = 'BRANCH';
    case SECTION = 'SECTION';

    /**
     * Determine whether this unit type may be placed under the given parent type.
     */
    public function acceptsParent(?self $parentType): bool
    {
        if ($parentType === null) {
            return $this === self::ROOT;
        }

        return in_array($parentType, $this->allowedParentTypes(), true);
    }

    /**
     * Get the organizational unit types that may contain this type.
     *
     * @return array<int, self>
     */
    public function allowedParentTypes(): array
    {
        return match ($this) {
            self::ROOT => [],
            self::HEAD_OFFICE => [self::ROOT],
            self::NDC => [self::ROOT, self::HEAD_OFFICE],
            self::ZONAL_OFFICE => [self::ROOT, self::HEAD_OFFICE],
            self::REGIONAL_OFFICE => [self::ZONAL_OFFICE, self::SPECIAL_STATE_OFFICE],
            self::DISTRICT_OFFICE => [self::REGIONAL_OFFICE, self::SPECIAL_STATE_OFFICE],
            self::SPECIAL_STATE_OFFICE => [self::ROOT, self::HEAD_OFFICE, self::ZONAL_OFFICE, self::REGIONAL_OFFICE],
            self::VIGILANCE_HQ => [self::ROOT, self::HEAD_OFFICE],
            self::VIGILANCE_ZVD => [self::VIGILANCE_HQ],
            self::INTERNAL_AUDIT_WING => [self::HEAD_OFFICE],
            self::PDUNASS => [self::ROOT, self::HEAD_OFFICE],
            self::ZTI => [self::PDUNASS],
            self::HOLIDAY_HOME => [self::HEAD_OFFICE],
            self::ALTERNATE_DATA_CENTRE => [self::NDC],
            self::DIRECTORATE => [
                self::HEAD_OFFICE,
                self::NDC,
                self::ZONAL_OFFICE,
                self::REGIONAL_OFFICE,
                self::SPECIAL_STATE_OFFICE,
                self::VIGILANCE_HQ,
                self::INTERNAL_AUDIT_WING,
                self::PDUNASS,
            ],
            self::DIVISION => [
                self::HEAD_OFFICE,
                self::NDC,
                self::ZONAL_OFFICE,
                self::REGIONAL_OFFICE,
                self::DISTRICT_OFFICE,
                self::SPECIAL_STATE_OFFICE,
                self::VIGILANCE_HQ,
                self::VIGILANCE_ZVD,
                self::INTERNAL_AUDIT_WING,
                self::PDUNASS,
                self::ZTI,
                self::ALTERNATE_DATA_CENTRE,
                self::DIRECTORATE,
            ],
            self::BRANCH => [
                self::HEAD_OFFICE,
                self::NDC,
                self::ZONAL_OFFICE,
                self::REGIONAL_OFFICE,
                self::DISTRICT_OFFICE,
                self::SPECIAL_STATE_OFFICE,
                self::VIGILANCE_HQ,
                self::VIGILANCE_ZVD,
                self::INTERNAL_AUDIT_WING,
                self::PDUNASS,
                self::ZTI,
                self::ALTERNATE_DATA_CENTRE,
                self::DIRECTORATE,
                self::DIVISION,
            ],
            self::SECTION => [
                self::NDC,
                self::ZONAL_OFFICE,
                self::REGIONAL_OFFICE,
                self::DISTRICT_OFFICE,
                self::SPECIAL_STATE_OFFICE,
                self::VIGILANCE_ZVD,
                self::INTERNAL_AUDIT_WING,
                self::ZTI,
                self::ALTERNATE_DATA_CENTRE,
                self::DIRECTORATE,
                self::DIVISION,
                self::BRANCH,
            ],
        };
    }
}
