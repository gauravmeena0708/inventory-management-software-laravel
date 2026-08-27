<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\LocationType;
use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\CreateAssetAction;
use App\Services\Inventory\PostStockEntryAction;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PocInventoryDemoSeeder extends Seeder
{
    /**
     * Seed a small, organization-aware dataset for the POC workflows.
     */
    public function run(
        PhysicalHierarchyService $physicalHierarchy,
        CreateAssetAction $createAsset,
        AssignAssetAction $assignAsset,
        PostStockEntryAction $postStockEntry
    ): void {
        $this->call(EpfoOfficeDirectorySeeder::class);

        $unit = OrganizationalUnit::query()->where('code', 'NDC')->firstOrFail();
        $site = Site::query()->where('code', 'NDC_HQ')->firstOrFail();

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@inventory.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager@inventory.local'],
            [
                'name' => 'Inventory Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::INVENTORY_MANAGER,
                'email_verified_at' => now(),
            ]
        );

        foreach ([$admin, $manager] as $user) {
            $user->organizationalUnits()->syncWithoutDetaching([
                $unit->id => [
                    'read_scope' => 'descendants',
                    'write_scope' => 'descendants',
                    'valid_from' => now(),
                    'valid_until' => null,
                ],
            ]);

            if (! $user->default_organizational_unit_id) {
                $user->forceFill(['default_organizational_unit_id' => $unit->id])->save();
            }
        }

        $locations = $this->seedLocations($site, $physicalHierarchy);
        $people = $this->seedPeople($locations);

        $this->seedAssets($unit, $locations, $people, $admin, $createAsset, $assignAsset);
        $this->seedConsumables($people, $manager, $postStockEntry);
    }

    /**
     * @return array<string, Location>
     */
    private function seedLocations(Site $site, PhysicalHierarchyService $physicalHierarchy): array
    {
        $building = $this->location($site, null, $physicalHierarchy, [
            'code' => 'POC-MAIN-BUILDING',
            'name' => 'Main Office Building',
            'location_type' => LocationType::BUILDING,
            'building' => 'Main Office Building',
            'floor' => 'Ground',
            'description' => 'Primary office used for the inventory POC.',
        ]);

        $floor = $this->location($site, $building, $physicalHierarchy, [
            'code' => 'POC-GROUND-FLOOR',
            'name' => 'Ground Floor',
            'location_type' => LocationType::FLOOR,
            'building' => 'Main Office Building',
            'floor' => 'Ground',
            'level_number' => 0,
        ]);

        return [
            'building' => $building,
            'floor' => $floor,
            'office' => $this->location($site, $floor, $physicalHierarchy, [
                'code' => 'POC-OPERATIONS-ROOM',
                'name' => 'Operations Room',
                'location_type' => LocationType::ROOM,
                'building' => 'Main Office Building',
                'floor' => 'Ground',
                'sublocation' => 'Room G-01',
            ]),
            'store' => $this->location($site, $floor, $physicalHierarchy, [
                'code' => 'POC-IT-STORE',
                'name' => 'IT Store',
                'location_type' => LocationType::STORE,
                'building' => 'Main Office Building',
                'floor' => 'Ground',
                'sublocation' => 'Room G-02',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function location(
        Site $site,
        ?Location $parent,
        PhysicalHierarchyService $physicalHierarchy,
        array $data
    ): Location {
        $existing = Location::query()
            ->where('site_id', $site->id)
            ->where('code', $data['code'])
            ->first();

        return $existing ?? $physicalHierarchy->createLocation([
            ...$data,
            'location_type' => $data['location_type']->value,
            'site_id' => $site->id,
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, Location>  $locations
     * @return array<string, Official>
     */
    private function seedPeople(array $locations): array
    {
        $people = [
            'aditi' => [
                'name' => 'Aditi Sharma',
                'title' => 'Ms.',
                'designation' => 'Operations Manager',
                'department' => 'Operations',
                'email' => 'aditi.sharma@poc.example.test',
                'phone' => '+91 90000 10001',
                'location_id' => $locations['office']->id,
            ],
            'rohit' => [
                'name' => 'Rohit Verma',
                'title' => 'Mr.',
                'designation' => 'IT Support Engineer',
                'department' => 'Information Technology',
                'email' => 'rohit.verma@poc.example.test',
                'phone' => '+91 90000 10002',
                'location_id' => $locations['office']->id,
            ],
            'neha' => [
                'name' => 'Neha Kapoor',
                'title' => 'Ms.',
                'designation' => 'Finance Executive',
                'department' => 'Finance',
                'email' => 'neha.kapoor@poc.example.test',
                'phone' => '+91 90000 10003',
                'location_id' => $locations['office']->id,
            ],
            'vikram' => [
                'name' => 'Vikram Singh',
                'title' => 'Mr.',
                'designation' => 'Store Coordinator',
                'department' => 'Administration',
                'email' => 'vikram.singh@poc.example.test',
                'phone' => '+91 90000 10004',
                'location_id' => $locations['store']->id,
            ],
            'priya' => [
                'name' => 'Priya Nair',
                'title' => 'Ms.',
                'designation' => 'HR Executive',
                'department' => 'Human Resources',
                'email' => 'priya.nair@poc.example.test',
                'phone' => '+91 90000 10005',
                'location_id' => $locations['office']->id,
            ],
        ];

        foreach ($people as $key => $data) {
            $people[$key] = Official::query()->firstOrCreate(['email' => $data['email']], $data);
        }

        return $people;
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, Official>  $people
     */
    private function seedAssets(
        OrganizationalUnit $unit,
        array $locations,
        array $people,
        User $admin,
        CreateAssetAction $createAsset,
        AssignAssetAction $assignAsset
    ): void {
        $assets = [
            ['tag' => 'POC-LAP-001', 'name' => 'Dell Latitude 5440', 'type' => AssetType::LAPTOP, 'location' => 'office', 'serial' => 'POC-DL5440-001', 'assignee' => 'aditi'],
            ['tag' => 'POC-DESK-001', 'name' => 'HP ProDesk 400 G9', 'type' => AssetType::DESKTOP, 'location' => 'office', 'serial' => 'POC-HP400-001', 'assignee' => 'rohit'],
            ['tag' => 'POC-LAP-002', 'name' => 'Lenovo ThinkPad E14', 'type' => AssetType::LAPTOP, 'location' => 'store', 'serial' => 'POC-TPE14-002'],
            ['tag' => 'POC-SRV-001', 'name' => 'Dell PowerEdge T350', 'type' => AssetType::SERVER, 'location' => 'office', 'serial' => 'POC-PET350-001', 'assignee' => 'neha'],
            ['tag' => 'POC-SW-001', 'name' => 'Cisco Catalyst 1000 Switch', 'type' => AssetType::SWITCH, 'location' => 'office', 'serial' => 'POC-CAT1000-001', 'status' => AssetStatus::UNDER_MAINTENANCE],
            ['tag' => 'POC-SSD-001', 'name' => 'Portable SSD 1 TB', 'type' => AssetType::STORAGE, 'location' => 'store', 'serial' => 'POC-SSD1TB-001'],
            ['tag' => 'POC-PRN-001', 'name' => 'Network Laser Printer', 'type' => AssetType::OTHER, 'location' => 'office', 'serial' => 'POC-PRINTER-001'],
            ['tag' => 'POC-UPS-001', 'name' => 'Legacy 1 KVA UPS', 'type' => AssetType::OTHER, 'location' => 'store', 'serial' => 'POC-UPS1K-001', 'status' => AssetStatus::DECOMMISSIONED],
        ];

        foreach ($assets as $item) {
            $asset = Asset::query()->where('asset_tag', $item['tag'])->first();

            if (! $asset) {
                $asset = $createAsset->execute([
                    'asset_tag' => $item['tag'],
                    'name' => $item['name'],
                    'asset_type' => $item['type'],
                    'status' => $item['status'] ?? AssetStatus::IN_STOCK,
                    'serial_number' => $item['serial'],
                    'location_id' => $locations[$item['location']]->id,
                    'organizational_unit_id' => $unit->id,
                    'purchase_date' => now()->subMonths(6)->toDateString(),
                    'currency' => 'INR',
                    'remarks' => 'Synthetic POC demonstration data.',
                ], $admin);
            }

            if (isset($item['assignee']) && ! $asset->assignments()->exists()) {
                $assignAsset->execute(
                    asset: $asset,
                    official: $people[$item['assignee']],
                    actor: $admin,
                    remarks: 'Initial POC assignment.',
                    conditionOut: 'Good',
                    assignedAt: now()->subDays(30)
                );
            }
        }
    }

    /**
     * @param  array<string, Official>  $people
     */
    private function seedConsumables(
        array $people,
        User $manager,
        PostStockEntryAction $postStockEntry
    ): void {
        $consumables = [
            ['sku' => 'POC-PAPER-A4', 'name' => 'A4 Copier Paper', 'unit' => 'ream', 'min' => 10, 'max' => 60, 'received' => 40, 'issued' => 8, 'recipient' => 'priya'],
            ['sku' => 'POC-TONER-BLK', 'name' => 'Black Printer Toner', 'unit' => 'cartridge', 'min' => 5, 'max' => 20, 'received' => 12, 'issued' => 9, 'recipient' => 'neha'],
            ['sku' => 'POC-MOUSE-WL', 'name' => 'Wireless Mouse', 'unit' => 'piece', 'min' => 5, 'max' => 25, 'received' => 18, 'issued' => 4, 'recipient' => 'rohit'],
            ['sku' => 'POC-CABLE-CAT6', 'name' => 'Cat6 Network Cable', 'unit' => 'piece', 'min' => 12, 'max' => 80, 'received' => 50, 'issued' => 15, 'recipient' => 'rohit'],
            ['sku' => 'POC-BATTERY-AA', 'name' => 'AA Battery Pack', 'unit' => 'pack', 'min' => 6, 'max' => 30, 'received' => 24, 'issued' => 20, 'recipient' => 'vikram'],
        ];

        foreach ($consumables as $item) {
            $consumable = Consumable::query()->firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'unit' => $item['unit'],
                    'in_stock' => 0,
                    'min_quantity' => $item['min'],
                    'max_quantity' => $item['max'],
                ]
            );

            $postStockEntry->execute(
                consumable: $consumable,
                type: StockEntryType::PURCHASE,
                quantity: $item['received'],
                recipient: null,
                user: $manager,
                remarks: 'Opening stock for the POC demonstration.',
                idempotencyKey: 'POC-RECEIVE-'.$item['sku']
            );

            $postStockEntry->execute(
                consumable: $consumable,
                type: StockEntryType::ISSUE,
                quantity: $item['issued'],
                recipient: $people[$item['recipient']],
                user: $manager,
                remarks: 'Sample stock issue for the POC demonstration.',
                idempotencyKey: 'POC-ISSUE-'.$item['sku']
            );
        }
    }
}
