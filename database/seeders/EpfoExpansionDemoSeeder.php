<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\Assets\CreateAssetAction;
use App\Services\Organization\OrganizationalContext;
use App\Services\Organization\OrganizationalHierarchyService;
use App\Services\Spatial\PhysicalHierarchyService;
use App\Services\Stock\LocationStockService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Development/test proof that another office can be loaded from data alone.
 *
 * This seeder deliberately depends only on the EPFO root contract and the
 * application's public hierarchy, asset, and stock services. It is not called
 * by DatabaseSeeder and must never be used as production reference data.
 */
class EpfoExpansionDemoSeeder extends Seeder
{
    /** @var array<string, mixed> */
    private const DEMO = [
        'root_code' => 'EPFO',
        'unit' => [
            'code' => 'ZO-NORTH-DEMO',
            'name' => 'North Zone Demonstration Office',
            'unit_type' => OrganizationalUnitType::ZONAL_OFFICE,
        ],
        'site' => [
            'code' => 'NORTH-DEMO-SITE',
            'name' => 'North Zone Demonstration Site',
            'address' => 'Demonstration data - not a real EPFO office',
            'latitude' => 28.6139000,
            'longitude' => 77.2090000,
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
        ],
        'locations' => [
            [
                'key' => 'building',
                'code' => 'NORTH-DEMO-BUILDING',
                'name' => 'North Zone Demonstration Building',
                'floor' => 'Ground',
                'location_type' => LocationType::BUILDING,
            ],
            [
                'key' => 'floor',
                'parent' => 'building',
                'code' => 'NORTH-DEMO-FLOOR-1',
                'name' => 'First Floor',
                'floor' => '1',
                'level_number' => 1,
                'location_type' => LocationType::FLOOR,
            ],
            [
                'key' => 'store',
                'parent' => 'floor',
                'code' => 'NORTH-DEMO-STORE',
                'name' => 'North Zone Demonstration Store',
                'floor' => '1',
                'location_type' => LocationType::STORE,
            ],
        ],
        'user' => [
            'name' => 'North Zone Demo Manager',
            'email' => 'north-zone-demo@example.test',
            'password' => 'demo-password-change-me',
            'role' => UserRole::INVENTORY_MANAGER,
        ],
        'asset' => [
            'asset_tag' => 'NORTH-DEMO-ASSET-001',
            'name' => 'North Zone Demonstration Laptop',
            'asset_type' => AssetType::LAPTOP,
            'status' => AssetStatus::IN_STOCK,
            'serial_number' => 'NORTH-DEMO-SERIAL-001',
            'remarks' => 'Non-production expansion proof data.',
        ],
        'stock' => [
            'sku' => 'NORTH-DEMO-PAPER',
            'name' => 'North Zone Demonstration Paper',
            'unit' => 'ream',
            'quantity' => 20,
            'min_quantity' => 5,
            'max_quantity' => 50,
        ],
    ];

    public function run(
        OrganizationalHierarchyService $organizations,
        PhysicalHierarchyService $physicalHierarchy,
        OrganizationalContext $context,
        CreateAssetAction $createAsset,
        LocationStockService $stock
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The EPFO expansion demo seeder is restricted to local and testing environments.');
        }

        $root = OrganizationalUnit::query()
            ->where('code', self::DEMO['root_code'])
            ->active()
            ->firstOrFail();

        $unitData = self::DEMO['unit'];
        $unit = OrganizationalUnit::query()->where('code', $unitData['code'])->first();
        if (! $unit) {
            $unit = $organizations->createUnit([
                ...$unitData,
                'unit_type' => $unitData['unit_type']->value,
                'parent_id' => $root->id,
                'is_active' => true,
                'metadata' => ['demo' => true],
            ]);
        }

        $siteData = self::DEMO['site'];
        $site = Site::query()->where('code', $siteData['code'])->first();
        if (! $site) {
            $site = $physicalHierarchy->createSite($siteData);
        }
        $unit->sites()->syncWithoutDetaching([$site->id]);

        /** @var array<string, Location> $locations */
        $locations = [];
        foreach (self::DEMO['locations'] as $configuredLocation) {
            $key = $configuredLocation['key'];
            $parentKey = $configuredLocation['parent'] ?? null;
            unset($configuredLocation['key'], $configuredLocation['parent']);

            $location = Location::query()
                ->where('site_id', $site->id)
                ->where('code', $configuredLocation['code'])
                ->first();

            if (! $location) {
                $location = $physicalHierarchy->createLocation([
                    ...$configuredLocation,
                    'location_type' => $configuredLocation['location_type']->value,
                    'site_id' => $site->id,
                    'parent_id' => $parentKey ? $locations[$parentKey]->id : null,
                    'is_active' => true,
                ]);
            }

            $locations[$key] = $location;
        }

        $userData = self::DEMO['user'];
        $manager = User::query()->updateOrCreate(
            ['email' => $userData['email']],
            $userData
        );
        $manager->organizationalUnits()->syncWithoutDetaching([
            $unit->id => [
                'read_scope' => 'local',
                'write_scope' => 'local',
                'valid_from' => now(),
                'valid_until' => null,
            ],
        ]);
        $context->setDefaultUnit($manager, $unit->id);

        if (! Asset::query()->where('asset_tag', self::DEMO['asset']['asset_tag'])->exists()) {
            $createAsset->execute([
                ...self::DEMO['asset'],
                'asset_type' => self::DEMO['asset']['asset_type']->value,
                'status' => self::DEMO['asset']['status']->value,
                'location_id' => $locations['store']->id,
                'organizational_unit_id' => $unit->id,
            ], $manager);
        }

        $stockData = self::DEMO['stock'];
        $consumable = Consumable::query()->updateOrCreate(
            ['sku' => $stockData['sku']],
            [
                'name' => $stockData['name'],
                'unit' => $stockData['unit'],
                'min_quantity' => $stockData['min_quantity'],
                'max_quantity' => $stockData['max_quantity'],
            ]
        );

        if (! StockBalance::query()
            ->where('consumable_id', $consumable->id)
            ->where('location_id', $locations['store']->id)
            ->exists()) {
            $stock->purchase(
                $consumable,
                $locations['store'],
                $stockData['quantity'],
                $manager,
                'north-demo-opening-stock',
                'Expansion proof opening balance.'
            );
        }
    }
}
