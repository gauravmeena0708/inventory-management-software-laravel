<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\PaymentStatus;
use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Models\Payment;
use App\Models\User;
use App\Services\Agreements\CompletePaymentAction;
use App\Services\Agreements\GeneratePaymentScheduleAction;
use App\Services\Assets\AssignAssetAction;
use App\Services\Inventory\PostStockEntryAction;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FictionalDatabaseSeeder extends Seeder
{
    /**
     * Run the synthetic, privacy-compliant database seeders.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // =========================================================================
        // 1. Role-Based Synthetic Users (6 Standard Personas)
        // =========================================================================
        $defaultPassword = Hash::make('password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@inventory.local'],
            [
                'name' => 'System Administrator',
                'password' => $defaultPassword,
                'role' => UserRole::ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@inventory.local'],
            [
                'name' => 'Inventory Manager',
                'password' => $defaultPassword,
                'role' => UserRole::INVENTORY_MANAGER,
                'email_verified_at' => now(),
            ]
        );

        $stockOp = User::firstOrCreate(
            ['email' => 'stock@inventory.local'],
            [
                'name' => 'Stock Operator',
                'password' => $defaultPassword,
                'role' => UserRole::STOCK_OPERATOR,
                'email_verified_at' => now(),
            ]
        );

        $financeOp = User::firstOrCreate(
            ['email' => 'finance@inventory.local'],
            [
                'name' => 'Finance Operator',
                'password' => $defaultPassword,
                'role' => UserRole::FINANCE_OPERATOR,
                'email_verified_at' => now(),
            ]
        );

        $auditor = User::firstOrCreate(
            ['email' => 'auditor@inventory.local'],
            [
                'name' => 'Compliance Auditor',
                'password' => $defaultPassword,
                'role' => UserRole::AUDITOR,
                'email_verified_at' => now(),
            ]
        );

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@inventory.local'],
            [
                'name' => 'Read-Only Viewer',
                'password' => $defaultPassword,
                'role' => UserRole::VIEWER,
                'email_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 2. Locations (5 Real-world Operational Facilities)
        // =========================================================================
        $locationsData = [
            [
                'name' => 'HQ',
                'building' => 'Executive Building A',
                'floor' => '1',
                'sublocation' => 'Main Wing',
                'description' => 'Corporate headquarters and executive management offices',
            ],
            [
                'name' => 'Server Room A',
                'building' => 'Operations Tower B',
                'floor' => 'Basement 1',
                'sublocation' => 'Racks 01-08',
                'description' => 'Primary high-density on-premise datacenter facility',
            ],
            [
                'name' => 'Lab 1',
                'building' => 'Engineering Center C',
                'floor' => '2',
                'sublocation' => 'Room 204',
                'description' => 'Hardware testing, diagnostics, and quality staging lab',
            ],
            [
                'name' => 'Floor 2',
                'building' => 'Executive Building A',
                'floor' => '2',
                'sublocation' => 'Open Workstation Bay',
                'description' => 'Software engineering and product development workspace',
            ],
            [
                'name' => 'Warehouse',
                'building' => 'Logistics Depot D',
                'floor' => 'Ground',
                'sublocation' => 'Bay 3 Shelf Units',
                'description' => 'Central IT inventory spares, staging, and storage warehouse',
            ],
        ];

        $locations = collect();
        foreach ($locationsData as $loc) {
            $locations->push(Location::firstOrCreate(['name' => $loc['name']], $loc));
        }

        // =========================================================================
        // 3. Manufacturers (5 Global Hardware & Network Vendors)
        // =========================================================================
        $manufacturersData = [
            [
                'name' => 'Dell',
                'support_contact' => '+1-800-624-9896',
                'website' => 'https://www.dell.com',
                'remarks' => 'Enterprise servers, Precision workstations, and Latitude laptops',
            ],
            [
                'name' => 'HP',
                'support_contact' => '+1-800-474-6836',
                'website' => 'https://www.hp.com',
                'remarks' => 'Enterprise ProLiant servers, EliteBook notebooks, and laser printers',
            ],
            [
                'name' => 'Cisco',
                'support_contact' => '+1-800-553-6387',
                'website' => 'https://www.cisco.com',
                'remarks' => 'Catalyst switches, Nexus datacenter switches, and ASA/Firepower security',
            ],
            [
                'name' => 'Lenovo',
                'support_contact' => '+1-855-253-6686',
                'website' => 'https://www.lenovo.com',
                'remarks' => 'ThinkPad mobile workstations and ThinkCentre micro desktops',
            ],
            [
                'name' => 'Apple',
                'support_contact' => '+1-800-275-2273',
                'website' => 'https://www.apple.com',
                'remarks' => 'MacBook Pro Apple Silicon workstations and developer hardware',
            ],
        ];

        $manufacturers = collect();
        foreach ($manufacturersData as $mfg) {
            $manufacturers->push(Manufacturer::firstOrCreate(['name' => $mfg['name']], $mfg));
        }

        $dell = $manufacturers->firstWhere('name', 'Dell');
        $hp = $manufacturers->firstWhere('name', 'HP');
        $cisco = $manufacturers->firstWhere('name', 'Cisco');
        $lenovo = $manufacturers->firstWhere('name', 'Lenovo');
        $apple = $manufacturers->firstWhere('name', 'Apple');

        $locHQ = $locations->firstWhere('name', 'HQ');
        $locServerRoom = $locations->firstWhere('name', 'Server Room A');
        $locLab = $locations->firstWhere('name', 'Lab 1');
        $locFloor2 = $locations->firstWhere('name', 'Floor 2');
        $locWarehouse = $locations->firstWhere('name', 'Warehouse');

        // =========================================================================
        // 4. Officials (10 Synthetic Staff Personas)
        // =========================================================================
        $officialsList = [
            ['name' => 'Alexander Hayes', 'title' => 'Mr.', 'designation' => 'Lead DevOps Architect', 'dept' => 'Engineering', 'loc' => $locFloor2],
            ['name' => 'Beatrice Vance', 'title' => 'Dr.', 'designation' => 'Principal Data Scientist', 'dept' => 'Engineering', 'loc' => $locFloor2],
            ['name' => 'Charles Sterling', 'title' => 'Mr.', 'designation' => 'Chief Technology Officer', 'dept' => 'Executive', 'loc' => $locHQ],
            ['name' => 'Diana Morales', 'title' => 'Ms.', 'designation' => 'Senior Network Engineer', 'dept' => 'Operations', 'loc' => $locServerRoom],
            ['name' => 'Edward Thorne', 'title' => 'Mr.', 'designation' => 'Database Administrator', 'dept' => 'Operations', 'loc' => $locServerRoom],
            ['name' => 'Fiona Gallagher', 'title' => 'Ms.', 'designation' => 'Finance Director', 'dept' => 'Finance', 'loc' => $locHQ],
            ['name' => 'George Kimball', 'title' => 'Mr.', 'designation' => 'IT Support Specialist', 'dept' => 'IT Support', 'loc' => $locLab],
            ['name' => 'Hannah Abbott', 'title' => 'Ms.', 'designation' => 'QA Automation Lead', 'dept' => 'Engineering', 'loc' => $locFloor2],
            ['name' => 'Ian MacIntyre', 'title' => 'Mr.', 'designation' => 'Security Analyst', 'dept' => 'Security', 'loc' => $locLab],
            ['name' => 'Julia Santos', 'title' => 'Ms.', 'designation' => 'Inventory Coordinator', 'dept' => 'Logistics', 'loc' => $locWarehouse],
        ];

        $officials = collect();
        foreach ($officialsList as $index => $item) {
            $email = strtolower(str_replace(' ', '.', $item['name'])) . '@company.local';
            $official = Official::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $item['name'],
                    'title' => $item['title'],
                    'designation' => $item['designation'],
                    'phone' => '+1-555-0' . str_pad((string) ($index + 100), 3, '0', STR_PAD_LEFT),
                    'department' => $item['dept'],
                    'location_id' => $item['loc']?->id,
                ]
            );
            $officials->push($official);
        }

        // =========================================================================
        // 5. Assets (15 Diverse Assets with Assignments and Statuses)
        // =========================================================================
        $assetsData = [
            // Laptops (4)
            [
                'name' => 'ThinkPad P16 Gen 2',
                'asset_type' => AssetType::LAPTOP,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'LEN-P16-9901',
                'asset_tag' => 'TAG-LAP-001',
                'model_number' => '21FA000DUS',
                'manufacturer_id' => $lenovo->id,
                'location_id' => $locFloor2->id,
                'official' => $officials[0], // Alexander Hayes
                'purchase_date' => '2026-01-10',
                'purchase_cost' => 185000.00,
                'warranty_expiry' => '2029-01-10',
                'amc_end' => '2027-01-10',
                'specifications' => ['processor' => 'Intel Core i9-13980HX', 'ram' => '64GB DDR5', 'storage' => '2TB NVMe SSD', 'gpu' => 'NVIDIA RTX 3500 Ada'],
            ],
            [
                'name' => 'MacBook Pro 16" M3 Max',
                'asset_type' => AssetType::LAPTOP,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'APP-MBP-8802',
                'asset_tag' => 'TAG-LAP-002',
                'model_number' => 'MUW63LL/A',
                'manufacturer_id' => $apple->id,
                'location_id' => $locFloor2->id,
                'official' => $officials[1], // Beatrice Vance
                'purchase_date' => '2026-02-01',
                'purchase_cost' => 320000.00,
                'warranty_expiry' => '2029-02-01',
                'amc_end' => '2027-02-01',
                'specifications' => ['processor' => 'Apple M3 Max (16-core CPU, 40-core GPU)', 'ram' => '64GB Unified', 'storage' => '1TB SSD'],
            ],
            [
                'name' => 'Dell Latitude 7440',
                'asset_type' => AssetType::LAPTOP,
                'status' => AssetStatus::IN_STOCK,
                'serial_number' => 'DEL-LAT-7403',
                'asset_tag' => 'TAG-LAP-003',
                'model_number' => 'LAT-7440-UL',
                'manufacturer_id' => $dell->id,
                'location_id' => $locWarehouse->id,
                'official' => null,
                'purchase_date' => '2026-01-20',
                'purchase_cost' => 95000.00,
                'warranty_expiry' => '2028-01-20',
                'amc_end' => null,
                'specifications' => ['processor' => 'Intel Core i7-1365U', 'ram' => '16GB LPDDR5', 'storage' => '512GB NVMe SSD'],
            ],
            [
                'name' => 'HP EliteBook 840 G10',
                'asset_type' => AssetType::LAPTOP,
                'status' => AssetStatus::UNDER_MAINTENANCE,
                'serial_number' => 'HP-EB-8404',
                'asset_tag' => 'TAG-LAP-004',
                'model_number' => 'EB-840-G10',
                'manufacturer_id' => $hp->id,
                'location_id' => $locLab->id,
                'official' => null,
                'purchase_date' => '2025-06-15',
                'purchase_cost' => 88000.00,
                'warranty_expiry' => '2028-06-15',
                'amc_end' => '2026-06-15',
                'specifications' => ['processor' => 'Intel Core i5-1345U', 'ram' => '16GB DDR5', 'storage' => '512GB SSD'],
            ],

            // Desktops (3)
            [
                'name' => 'Dell OptiPlex 7010 Micro',
                'asset_type' => AssetType::DESKTOP,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'DEL-OPT-7001',
                'asset_tag' => 'TAG-DSK-001',
                'model_number' => 'OPT-7010-MFF',
                'manufacturer_id' => $dell->id,
                'location_id' => $locHQ->id,
                'official' => $officials[2], // Charles Sterling
                'purchase_date' => '2025-11-01',
                'purchase_cost' => 65000.00,
                'warranty_expiry' => '2028-11-01',
                'amc_end' => null,
                'specifications' => ['processor' => 'Intel Core i7-13700T', 'ram' => '32GB DDR5', 'storage' => '1TB NVMe SSD'],
            ],
            [
                'name' => 'HP ProDesk 400 G9',
                'asset_type' => AssetType::DESKTOP,
                'status' => AssetStatus::IN_STOCK,
                'serial_number' => 'HP-PD-4002',
                'asset_tag' => 'TAG-DSK-002',
                'model_number' => 'PD-400-G9-SFF',
                'manufacturer_id' => $hp->id,
                'location_id' => $locWarehouse->id,
                'official' => null,
                'purchase_date' => '2025-12-10',
                'purchase_cost' => 52000.00,
                'warranty_expiry' => '2028-12-10',
                'amc_end' => null,
                'specifications' => ['processor' => 'Intel Core i5-13500', 'ram' => '16GB DDR4', 'storage' => '512GB SSD'],
            ],
            [
                'name' => 'Lenovo ThinkCentre M90q',
                'asset_type' => AssetType::DESKTOP,
                'status' => AssetStatus::DECOMMISSIONED,
                'serial_number' => 'LEN-TC-9003',
                'asset_tag' => 'TAG-DSK-003',
                'model_number' => '11DG001VUS',
                'manufacturer_id' => $lenovo->id,
                'location_id' => $locWarehouse->id,
                'official' => null,
                'purchase_date' => '2021-03-15',
                'purchase_cost' => 48000.00,
                'warranty_expiry' => '2024-03-15',
                'amc_end' => null,
                'specifications' => ['processor' => 'Intel Core i5-10500T', 'ram' => '8GB DDR4', 'storage' => '256GB SSD'],
            ],

            // Servers (3)
            [
                'name' => 'Dell PowerEdge R750',
                'asset_type' => AssetType::SERVER,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'DEL-R750-9101',
                'asset_tag' => 'TAG-SRV-001',
                'model_number' => 'PE-R750-2U',
                'manufacturer_id' => $dell->id,
                'location_id' => $locServerRoom->id,
                'official' => $officials[3], // Diana Morales
                'purchase_date' => '2025-08-01',
                'purchase_cost' => 620000.00,
                'warranty_expiry' => '2029-08-01',
                'amc_end' => '2027-08-01',
                'specifications' => ['cpu' => 'Dual Intel Xeon Gold 6338 32C/64T', 'ram' => '256GB DDR4 ECC', 'storage' => '8x 3.84TB SAS SSD RAID6'],
            ],
            [
                'name' => 'HP ProLiant DL380 Gen10',
                'asset_type' => AssetType::SERVER,
                'status' => AssetStatus::IN_STOCK,
                'serial_number' => 'HP-DL380-9102',
                'asset_tag' => 'TAG-SRV-002',
                'model_number' => 'P02462-B21',
                'manufacturer_id' => $hp->id,
                'location_id' => $locServerRoom->id,
                'official' => null,
                'purchase_date' => '2025-09-15',
                'purchase_cost' => 540000.00,
                'warranty_expiry' => '2028-09-15',
                'amc_end' => '2026-09-15',
                'specifications' => ['cpu' => 'Dual Intel Xeon Silver 4210R', 'ram' => '128GB DDR4 ECC', 'storage' => '6x 1.92TB SATA SSD RAID5'],
            ],
            [
                'name' => 'Dell PowerEdge R640',
                'asset_type' => AssetType::SERVER,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'DEL-R640-9103',
                'asset_tag' => 'TAG-SRV-003',
                'model_number' => 'PE-R640-1U',
                'manufacturer_id' => $dell->id,
                'location_id' => $locServerRoom->id,
                'official' => $officials[4], // Edward Thorne
                'purchase_date' => '2025-04-10',
                'purchase_cost' => 380000.00,
                'warranty_expiry' => '2028-04-10',
                'amc_end' => '2026-04-10',
                'specifications' => ['cpu' => 'Dual Intel Xeon Gold 5218', 'ram' => '128GB DDR4 ECC', 'storage' => '4x 960GB SSD RAID10'],
            ],

            // Switches (3)
            [
                'name' => 'Cisco Catalyst 9300 48-Port PoE+',
                'asset_type' => AssetType::SWITCH,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'CIS-CAT-9301',
                'asset_tag' => 'TAG-SWT-001',
                'model_number' => 'C9300-48P-A',
                'manufacturer_id' => $cisco->id,
                'location_id' => $locServerRoom->id,
                'official' => $officials[3], // Diana Morales
                'purchase_date' => '2025-05-20',
                'purchase_cost' => 280000.00,
                'warranty_expiry' => '2028-05-20',
                'amc_end' => '2027-05-20',
                'specifications' => ['ports' => '48x 1GbE PoE+ with 4x 10GbE Uplinks', 'switching_capacity' => '480 Gbps', 'stackable' => true],
            ],
            [
                'name' => 'Cisco Nexus 93180YC-FX',
                'asset_type' => AssetType::SWITCH,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'CIS-NEX-9302',
                'asset_tag' => 'TAG-SWT-002',
                'model_number' => 'N9K-C93180YC-FX',
                'manufacturer_id' => $cisco->id,
                'location_id' => $locServerRoom->id,
                'official' => $officials[3], // Diana Morales
                'purchase_date' => '2025-07-12',
                'purchase_cost' => 650000.00,
                'warranty_expiry' => '2029-07-12',
                'amc_end' => '2027-07-12',
                'specifications' => ['ports' => '48x 1/10/25-Gbps SFP28 and 6x 40/100-Gbps QSFP28', 'bandwidth' => '3.6 Tbps'],
            ],
            [
                'name' => 'Cisco Catalyst 2960-X 24TS-L',
                'asset_type' => AssetType::SWITCH,
                'status' => AssetStatus::IN_STOCK,
                'serial_number' => 'CIS-CAT-2903',
                'asset_tag' => 'TAG-SWT-003',
                'model_number' => 'WS-C2960X-24TS-L',
                'manufacturer_id' => $cisco->id,
                'location_id' => $locWarehouse->id,
                'official' => null,
                'purchase_date' => '2024-03-01',
                'purchase_cost' => 95000.00,
                'warranty_expiry' => '2027-03-01',
                'amc_end' => null,
                'specifications' => ['ports' => '24x 1GbE with 4x 1GbE SFP', 'stackable' => false],
            ],

            // Storage (2)
            [
                'name' => 'Dell PowerStore 500T SAN Array',
                'asset_type' => AssetType::STORAGE,
                'status' => AssetStatus::IN_USE,
                'serial_number' => 'DEL-PSTR-5001',
                'asset_tag' => 'TAG-STR-001',
                'model_number' => 'PSTORE-500T',
                'manufacturer_id' => $dell->id,
                'location_id' => $locServerRoom->id,
                'official' => $officials[4], // Edward Thorne
                'purchase_date' => '2025-10-01',
                'purchase_cost' => 1250000.00,
                'warranty_expiry' => '2030-10-01',
                'amc_end' => '2028-10-01',
                'specifications' => ['raw_capacity' => '25x 1.92TB NVMe SSD (48TB Raw)', 'protocols' => 'FC, iSCSI, NVMe-oF', 'controllers' => 'Dual Active-Active'],
            ],
            [
                'name' => 'HP MSA 2060 2.5in Storage',
                'asset_type' => AssetType::STORAGE,
                'status' => AssetStatus::IN_STOCK,
                'serial_number' => 'HP-MSA-2002',
                'asset_tag' => 'TAG-STR-002',
                'model_number' => 'R0Q75A',
                'manufacturer_id' => $hp->id,
                'location_id' => $locServerRoom->id,
                'official' => null,
                'purchase_date' => '2025-08-15',
                'purchase_cost' => 420000.00,
                'warranty_expiry' => '2028-08-15',
                'amc_end' => '2026-08-15',
                'specifications' => ['drives' => '12x 1.2TB 10K SAS HDD (14.4TB Raw)', 'interface' => '16Gb Fibre Channel'],
            ],
        ];

        $assignAction = app(AssignAssetAction::class);

        foreach ($assetsData as $item) {
            $official = $item['official'];
            unset($item['official']);

            /** @var Asset $asset */
            $asset = Asset::firstOrCreate(
                ['serial_number' => $item['serial_number']],
                $item
            );

            if ($official && $asset->status === AssetStatus::IN_USE) {
                // Ensure asset assignment history record is populated
                $existingAssignment = AssetAssignment::where('asset_id', $asset->id)
                    ->where('official_id', $official->id)
                    ->whereNull('returned_at')
                    ->first();

                if (! $existingAssignment) {
                    $assignAction->execute(
                        asset: $asset,
                        official: $official,
                        actor: $manager,
                        remarks: 'Deployed during initial IT setup',
                        conditionOut: 'Excellent'
                    );
                }
            }
        }

        // =========================================================================
        // 6. Consumables & Ledger Entries (10 Consumable Items)
        // =========================================================================
        $consumablesData = [
            ['name' => 'Cat6 Ethernet Patch Cable 3m', 'sku' => 'CON-ETH-3M', 'unit' => 'piece', 'min' => 15, 'max' => 150, 'init' => 60, 'issue' => 15],
            ['name' => 'A4 Printing Paper (Box of 5 Rims)', 'sku' => 'CON-PPR-A4', 'unit' => 'box', 'min' => 10, 'max' => 80, 'init' => 40, 'issue' => 12],
            ['name' => 'Wireless Ergonomic Optical Mouse', 'sku' => 'CON-MSE-WIR', 'unit' => 'piece', 'min' => 5, 'max' => 40, 'init' => 25, 'issue' => 8],
            ['name' => 'USB-C to HDMI/USB Multiport Adapter', 'sku' => 'CON-ADP-USBC', 'unit' => 'piece', 'min' => 8, 'max' => 50, 'init' => 30, 'issue' => 14],
            ['name' => 'HP LaserJet Black Toner 58A', 'sku' => 'CON-TNR-HP58', 'unit' => 'piece', 'min' => 4, 'max' => 20, 'init' => 12, 'issue' => 4],
            ['name' => 'High-Speed HDMI 2.1 Cable 2m', 'sku' => 'CON-CBL-HDMI', 'unit' => 'piece', 'min' => 10, 'max' => 60, 'init' => 35, 'issue' => 10],
            ['name' => 'AA Alkaline Long-Life Batteries 4pk', 'sku' => 'CON-BAT-AA', 'unit' => 'pack', 'min' => 10, 'max' => 80, 'init' => 50, 'issue' => 18],
            ['name' => 'Anti-Static Screen Cleaning Spray & Cloth', 'sku' => 'CON-CLN-SCR', 'unit' => 'set', 'min' => 5, 'max' => 30, 'init' => 20, 'issue' => 6],
            ['name' => 'DisplayPort to HDMI 4K Adapter', 'sku' => 'CON-ADP-DP4K', 'unit' => 'piece', 'min' => 5, 'max' => 30, 'init' => 18, 'issue' => 7],
            ['name' => 'Reusable Hook & Loop Cable Ties 100pk', 'sku' => 'CON-TIE-VEL', 'unit' => 'pack', 'min' => 5, 'max' => 50, 'init' => 30, 'issue' => 11],
        ];

        $postStockAction = app(PostStockEntryAction::class);

        foreach ($consumablesData as $idx => $conData) {
            $consumable = Consumable::firstOrCreate(
                ['sku' => $conData['sku']],
                [
                    'name' => $conData['name'],
                    'unit' => $conData['unit'],
                    'in_stock' => 0,
                    'min_quantity' => $conData['min'],
                    'max_quantity' => $conData['max'],
                ]
            );

            // Record initial stock purchase if no entries exist
            if ($consumable->entries()->count() === 0) {
                // 1. Initial Purchase
                $postStockAction->execute(
                    consumable: $consumable,
                    type: StockEntryType::PURCHASE,
                    quantity: $conData['init'],
                    recipient: null,
                    user: $stockOp,
                    remarks: "Initial replenishment batch PO-2026-" . (100 + $idx),
                    idempotencyKey: "INIT-PURCHASE-" . $conData['sku']
                );

                // 2. Initial Issuance to synthetic official
                if ($conData['issue'] > 0) {
                    $recipient = $officials[$idx % $officials->count()];
                    $postStockAction->execute(
                        consumable: $consumable,
                        type: StockEntryType::ISSUE,
                        quantity: $conData['issue'],
                        recipient: $recipient,
                        user: $stockOp,
                        remarks: "Allocated to " . $recipient->name . " for departmental operations",
                        idempotencyKey: "INIT-ISSUE-" . $conData['sku']
                    );
                }
            }
        }

        // =========================================================================
        // 7. Service Agreements & Generated Payment Schedules (3 Agreements)
        // =========================================================================
        $agreementsData = [
            [
                'name' => 'Cisco Smart Net Total Care AMC 2026',
                'agency' => 'Cisco Systems Services',
                'type' => 'Network Hardware AMC',
                'annual_cost' => 240000.00,
                'currency' => 'INR',
                'billing_interval_months' => 3, // Quarterly: 4 payments of 60,000 INR
                'billing_anchor_date' => '2026-01-01',
                'expiry' => '2026-12-31',
                'remarks' => '24x7 TAC support and next-business-day hardware replacement for core switches',
                'completed_milestones' => 1, // Complete Q1
            ],
            [
                'name' => 'Datacenter Precision Cooling & UPS AMC',
                'agency' => 'Vertiv Enterprise Solutions',
                'type' => 'Facilities & Power AMC',
                'annual_cost' => 180000.00,
                'currency' => 'INR',
                'billing_interval_months' => 6, // Semi-Annual: 2 payments of 90,000 INR
                'billing_anchor_date' => '2026-01-01',
                'expiry' => '2026-12-31',
                'remarks' => 'Quarterly preventive maintenance visits for datacenter PAC units and 60KVA UPS',
                'completed_milestones' => 1, // Complete H1
            ],
            [
                'name' => 'Enterprise Cloud Infrastructure Support SLA',
                'agency' => 'Amazon Web Services Premier Support',
                'type' => 'Cloud Support & SLA',
                'annual_cost' => 360000.00,
                'currency' => 'INR',
                'billing_interval_months' => 1, // Monthly: 12 payments of 30,000 INR
                'billing_anchor_date' => '2026-01-01',
                'expiry' => '2026-12-31',
                'remarks' => '15-minute response SLA for critical business services and architecture reviews',
                'completed_milestones' => 2, // Complete Jan and Feb
            ],
        ];

        $generateScheduleAction = app(GeneratePaymentScheduleAction::class);
        $completePaymentAction = app(CompletePaymentAction::class);

        foreach ($agreementsData as $idx => $agrData) {
            $completedMilestones = $agrData['completed_milestones'];
            unset($agrData['completed_milestones']);

            /** @var Agreement $agreement */
            $agreement = Agreement::firstOrCreate(
                ['name' => $agrData['name']],
                $agrData
            );

            // Generate idempotent payment schedule milestones
            $payments = $generateScheduleAction->execute($agreement);

            // Complete required past payment milestones
            $orderedPayments = $agreement->payments()->orderBy('due_date', 'asc')->get();
            for ($i = 0; $i < $completedMilestones && $i < $orderedPayments->count(); $i++) {
                /** @var Payment $payment */
                $payment = $orderedPayments[$i];
                if ($payment->status !== PaymentStatus::COMPLETED) {
                    $invoiceNumber = 'INV-' . strtoupper(substr(str_replace(' ', '', $agreement->agency), 0, 4)) . '-2026-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                    $paidDate = Carbon::parse($payment->due_date)->addDays(5);

                    $completePaymentAction->execute(
                        payment: $payment,
                        user: $financeOp,
                        invoiceNumber: $invoiceNumber,
                        paidDate: $paidDate
                    );
                }
            }
        }
    }
}
