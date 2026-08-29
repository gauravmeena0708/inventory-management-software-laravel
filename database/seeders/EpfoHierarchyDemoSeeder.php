<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use App\Services\Organization\OrganizationalHierarchyService;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Populate a broad, synthetic EPFO hierarchy for local demonstrations.
 *
 * Names describe plausible office levels and cities, but the records are not
 * authoritative EPFO reference data and must not be loaded in production.
 */
class EpfoHierarchyDemoSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string, type: OrganizationalUnitType, parent: string, city?: string, site?: bool}>
     */
    private const UNITS = [
        ['code' => 'HO-EPFO-DEMO', 'name' => 'EPFO Head Office', 'type' => OrganizationalUnitType::HEAD_OFFICE, 'parent' => 'EPFO', 'city' => 'New Delhi', 'site' => true],

        ['code' => 'ZO-NORTH', 'name' => 'North Zone Office', 'type' => OrganizationalUnitType::ZONAL_OFFICE, 'parent' => 'HO-EPFO-DEMO', 'city' => 'New Delhi', 'site' => true],
        ['code' => 'RO-DELHI', 'name' => 'Delhi Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-NORTH', 'city' => 'Delhi', 'site' => true],
        ['code' => 'DO-GURUGRAM', 'name' => 'Gurugram District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-DELHI', 'city' => 'Gurugram', 'site' => true],
        ['code' => 'DO-NOIDA', 'name' => 'Noida District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-DELHI', 'city' => 'Noida', 'site' => true],
        ['code' => 'RO-LUCKNOW', 'name' => 'Lucknow Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-NORTH', 'city' => 'Lucknow', 'site' => true],
        ['code' => 'DO-KANPUR', 'name' => 'Kanpur District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-LUCKNOW', 'city' => 'Kanpur', 'site' => true],
        ['code' => 'DO-VARANASI', 'name' => 'Varanasi District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-LUCKNOW', 'city' => 'Varanasi', 'site' => true],

        ['code' => 'ZO-WEST', 'name' => 'West Zone Office', 'type' => OrganizationalUnitType::ZONAL_OFFICE, 'parent' => 'HO-EPFO-DEMO', 'city' => 'Mumbai', 'site' => true],
        ['code' => 'RO-MUMBAI', 'name' => 'Mumbai Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-WEST', 'city' => 'Mumbai', 'site' => true],
        ['code' => 'DO-THANE', 'name' => 'Thane District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-MUMBAI', 'city' => 'Thane', 'site' => true],
        ['code' => 'DO-PUNE', 'name' => 'Pune District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-MUMBAI', 'city' => 'Pune', 'site' => true],
        ['code' => 'RO-AHMEDABAD', 'name' => 'Ahmedabad Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-WEST', 'city' => 'Ahmedabad', 'site' => true],
        ['code' => 'DO-SURAT', 'name' => 'Surat District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-AHMEDABAD', 'city' => 'Surat', 'site' => true],
        ['code' => 'DO-VADODARA', 'name' => 'Vadodara District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-AHMEDABAD', 'city' => 'Vadodara', 'site' => true],

        ['code' => 'ZO-SOUTH', 'name' => 'South Zone Office', 'type' => OrganizationalUnitType::ZONAL_OFFICE, 'parent' => 'HO-EPFO-DEMO', 'city' => 'Bengaluru', 'site' => true],
        ['code' => 'RO-BENGALURU', 'name' => 'Bengaluru Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-SOUTH', 'city' => 'Bengaluru', 'site' => true],
        ['code' => 'DO-MYSURU', 'name' => 'Mysuru District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-BENGALURU', 'city' => 'Mysuru', 'site' => true],
        ['code' => 'DO-MANGALURU', 'name' => 'Mangaluru District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-BENGALURU', 'city' => 'Mangaluru', 'site' => true],
        ['code' => 'RO-CHENNAI', 'name' => 'Chennai Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-SOUTH', 'city' => 'Chennai', 'site' => true],
        ['code' => 'DO-COIMBATORE', 'name' => 'Coimbatore District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-CHENNAI', 'city' => 'Coimbatore', 'site' => true],
        ['code' => 'DO-MADURAI', 'name' => 'Madurai District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-CHENNAI', 'city' => 'Madurai', 'site' => true],

        ['code' => 'ZO-EAST', 'name' => 'East Zone Office', 'type' => OrganizationalUnitType::ZONAL_OFFICE, 'parent' => 'HO-EPFO-DEMO', 'city' => 'Kolkata', 'site' => true],
        ['code' => 'RO-KOLKATA', 'name' => 'Kolkata Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-EAST', 'city' => 'Kolkata', 'site' => true],
        ['code' => 'DO-HOWRAH', 'name' => 'Howrah District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-KOLKATA', 'city' => 'Howrah', 'site' => true],
        ['code' => 'DO-DURGAPUR', 'name' => 'Durgapur District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-KOLKATA', 'city' => 'Durgapur', 'site' => true],
        ['code' => 'RO-PATNA', 'name' => 'Patna Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'ZO-EAST', 'city' => 'Patna', 'site' => true],
        ['code' => 'DO-GAYA', 'name' => 'Gaya District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-PATNA', 'city' => 'Gaya', 'site' => true],
        ['code' => 'DO-MUZAFFARPUR', 'name' => 'Muzaffarpur District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-PATNA', 'city' => 'Muzaffarpur', 'site' => true],

        ['code' => 'SSO-NORTHEAST', 'name' => 'North East Special State Office', 'type' => OrganizationalUnitType::SPECIAL_STATE_OFFICE, 'parent' => 'ZO-EAST', 'city' => 'Guwahati', 'site' => true],
        ['code' => 'SSO-GANGTOK', 'name' => 'Gangtok Special State Office', 'type' => OrganizationalUnitType::SPECIAL_STATE_OFFICE, 'parent' => 'ZO-EAST', 'city' => 'Gangtok', 'site' => true],
        ['code' => 'RO-GUWAHATI', 'name' => 'Guwahati Regional Office', 'type' => OrganizationalUnitType::REGIONAL_OFFICE, 'parent' => 'SSO-NORTHEAST', 'city' => 'Guwahati', 'site' => true],
        ['code' => 'DO-SHILLONG', 'name' => 'Shillong District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-GUWAHATI', 'city' => 'Shillong', 'site' => true],
        ['code' => 'DO-AGARTALA', 'name' => 'Agartala District Office', 'type' => OrganizationalUnitType::DISTRICT_OFFICE, 'parent' => 'RO-GUWAHATI', 'city' => 'Agartala', 'site' => true],

        ['code' => 'VIG-HQ-DEMO', 'name' => 'Central Vigilance Headquarters', 'type' => OrganizationalUnitType::VIGILANCE_HQ, 'parent' => 'HO-EPFO-DEMO'],
        ['code' => 'VIG-ZVD-NORTH', 'name' => 'North Zone Vigilance Directorate', 'type' => OrganizationalUnitType::VIGILANCE_ZVD, 'parent' => 'VIG-HQ-DEMO'],
        ['code' => 'VIG-ZVD-SOUTH', 'name' => 'South Zone Vigilance Directorate', 'type' => OrganizationalUnitType::VIGILANCE_ZVD, 'parent' => 'VIG-HQ-DEMO'],
        ['code' => 'VIG-ZVD-EAST', 'name' => 'East Zone Vigilance Directorate', 'type' => OrganizationalUnitType::VIGILANCE_ZVD, 'parent' => 'VIG-HQ-DEMO'],
        ['code' => 'VIG-ZVD-WEST', 'name' => 'West Zone Vigilance Directorate', 'type' => OrganizationalUnitType::VIGILANCE_ZVD, 'parent' => 'VIG-HQ-DEMO'],
        ['code' => 'IAW-DEMO', 'name' => 'Internal Audit Wing', 'type' => OrganizationalUnitType::INTERNAL_AUDIT_WING, 'parent' => 'HO-EPFO-DEMO', 'city' => 'New Delhi'],
        ['code' => 'PDUNASS-DEMO', 'name' => 'NATRSS / PDUNASS', 'type' => OrganizationalUnitType::PDUNASS, 'parent' => 'HO-EPFO-DEMO', 'city' => 'New Delhi'],
        ['code' => 'ZTI-NORTH-DEMO', 'name' => 'Zonal Training Institute - North', 'type' => OrganizationalUnitType::ZTI, 'parent' => 'PDUNASS-DEMO', 'city' => 'Faridabad', 'site' => true],
        ['code' => 'ZTI-SOUTH-DEMO', 'name' => 'Zonal Training Institute - South', 'type' => OrganizationalUnitType::ZTI, 'parent' => 'PDUNASS-DEMO', 'city' => 'Chennai', 'site' => true],
        ['code' => 'ZTI-EAST-DEMO', 'name' => 'Zonal Training Institute - East', 'type' => OrganizationalUnitType::ZTI, 'parent' => 'PDUNASS-DEMO', 'city' => 'Kolkata', 'site' => true],
        ['code' => 'ZTI-WEST-DEMO', 'name' => 'Zonal Training Institute - West', 'type' => OrganizationalUnitType::ZTI, 'parent' => 'PDUNASS-DEMO', 'city' => 'Ujjain', 'site' => true],
        ['code' => 'DIR-IS-DEMO', 'name' => 'Information Services Directorate', 'type' => OrganizationalUnitType::DIRECTORATE, 'parent' => 'HO-EPFO-DEMO'],
        ['code' => 'DIV-INFRA-DEMO', 'name' => 'Infrastructure Division', 'type' => OrganizationalUnitType::DIVISION, 'parent' => 'DIR-IS-DEMO'],
        ['code' => 'SEC-ASSET-DEMO', 'name' => 'Asset Management Section', 'type' => OrganizationalUnitType::SECTION, 'parent' => 'DIV-INFRA-DEMO'],
    ];

    public function run(
        OrganizationalHierarchyService $organizations,
        PhysicalHierarchyService $physicalHierarchy,
        OrganizationalContext $context
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The EPFO hierarchy demo seeder is restricted to local and testing environments.');
        }

        $units = [
            'EPFO' => OrganizationalUnit::query()->where('code', 'EPFO')->active()->firstOrFail(),
        ];

        foreach (self::UNITS as $configuredUnit) {
            $parent = $units[$configuredUnit['parent']]
                ?? OrganizationalUnit::query()->where('code', $configuredUnit['parent'])->active()->firstOrFail();

            $unit = OrganizationalUnit::query()->where('code', $configuredUnit['code'])->first();
            if (! $unit) {
                $unit = $organizations->createUnit([
                    'code' => $configuredUnit['code'],
                    'name' => $configuredUnit['name'],
                    'unit_type' => $configuredUnit['type']->value,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'metadata' => [
                        'demo' => true,
                        'city' => $configuredUnit['city'] ?? null,
                        'source' => 'pf-contacts-inspired',
                    ],
                ]);
            } elseif ($unit->unit_type !== $configuredUnit['type']) {
                throw new RuntimeException("Demo unit {$configuredUnit['code']} conflicts with an existing hierarchy record.");
            }

            if ($unit->parent_id !== $parent->id) {
                $organizations->moveUnit($unit, $parent);
            }

            $unit->forceFill([
                'name' => $configuredUnit['name'],
                'metadata' => [
                    ...($unit->metadata ?? []),
                    'demo' => true,
                    'city' => $configuredUnit['city'] ?? null,
                    'source' => 'pf-contacts-inspired',
                ],
            ])->save();

            $units[$configuredUnit['code']] = $unit;

            if ($configuredUnit['site'] ?? false) {
                $this->seedPhysicalOffice($unit, $configuredUnit, $physicalHierarchy);
            }
        }

        $admin = User::query()->where('email', 'admin@inventory.local')->first();
        if ($admin) {
            $root = $units['EPFO'];
            $admin->organizationalUnits()->syncWithoutDetaching([
                $root->id => [
                    'read_scope' => 'descendants',
                    'write_scope' => 'descendants',
                    'valid_from' => now(),
                    'valid_until' => null,
                ],
            ]);
            $context->setDefaultUnit($admin, $root->id);
        }

    }

    /**
     * @param  array{code: string, name: string, city?: string}  $configuredUnit
     */
    private function seedPhysicalOffice(
        OrganizationalUnit $unit,
        array $configuredUnit,
        PhysicalHierarchyService $physicalHierarchy
    ): void {
        $siteCode = $configuredUnit['code'].'-SITE';
        $site = Site::query()->where('code', $siteCode)->first();
        if (! $site) {
            $site = $physicalHierarchy->createSite([
                'code' => $siteCode,
                'name' => $configuredUnit['name'].' Campus',
                'address' => 'Synthetic demonstration campus, '.($configuredUnit['city'] ?? 'India'),
                'timezone' => 'Asia/Kolkata',
                'is_active' => true,
            ]);
        }
        $unit->sites()->syncWithoutDetaching([$site->id]);

        $building = $this->location($physicalHierarchy, $site, null, [
            'code' => $configuredUnit['code'].'-BLDG',
            'name' => $configuredUnit['name'].' Building',
            'location_type' => LocationType::BUILDING,
            'building' => 'Main Building',
            'floor' => 'Ground',
        ]);
        $floor = $this->location($physicalHierarchy, $site, $building, [
            'code' => $configuredUnit['code'].'-F1',
            'name' => 'First Floor',
            'location_type' => LocationType::FLOOR,
            'floor' => '1',
            'level_number' => 1,
        ]);
        $this->location($physicalHierarchy, $site, $floor, [
            'code' => $configuredUnit['code'].'-OFFICE',
            'name' => 'General Administration Office',
            'location_type' => LocationType::ROOM,
            'sublocation' => 'Administration',
            'floor' => '1',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function location(
        PhysicalHierarchyService $physicalHierarchy,
        Site $site,
        ?Location $parent,
        array $data
    ): Location {
        $location = Location::query()
            ->where('site_id', $site->id)
            ->where('code', $data['code'])
            ->first();

        return $location ?? $physicalHierarchy->createLocation([
            ...$data,
            'location_type' => $data['location_type']->value,
            'site_id' => $site->id,
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
    }
}
