<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\PaymentStatus;
use App\Enums\StockEntryType;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Consumable;
use App\Models\Devcat;
use App\Models\Developer;
use App\Models\Entry;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Models\Payment;
use App\Models\Task;
use App\Models\User;
use App\Services\Importer\LegacyImportManager;
use App\Services\Importer\ReconciliationReporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyImporterVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure legacy connection to point to testing database connection
        $defaultConn = config('database.default');
        config(['database.connections.legacy' => config("database.connections.{$defaultConn}")]);

        // Add any test-specific optional columns if not present in legacy test schema
        if (Schema::hasTable('desktops') && !Schema::hasColumn('desktops', 'processor')) {
            Schema::table('desktops', function (Blueprint $table) {
                $table->string('processor')->nullable();
                $table->string('ram')->nullable();
                $table->string('hdd')->nullable();
            });
        }

        if (Schema::hasTable('laptops') && !Schema::hasColumn('laptops', 'processor')) {
            Schema::table('laptops', function (Blueprint $table) {
                $table->string('processor')->nullable();
                $table->string('ram')->nullable();
                $table->string('hdd')->nullable();
            });
        }
    }

    /**
     * Test importer maps legacy asset tables (desktops, laptops, servers, devices, storages)
     * into unified assets table while preserving raw legacy payload.
     */
    public function test_importer_maps_legacy_tables_into_unified_assets_preserving_raw_payload(): void
    {
        // 1. Seed legacy desktops
        DB::connection('legacy')->table('desktops')->insert([
            'id' => 101,
            'serial' => 'SN-DESK-01',
            'brand' => 'HP',
            'category' => 'EliteDesk 800',
            'active' => 1,
            'file' => 'FILE-DESK-101',
            'purchased' => '2024-03-15',
            'processor' => 'i7-12700',
            'ram' => '16GB',
            'hdd' => '512GB SSD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed legacy servers
        DB::connection('legacy')->table('servers')->insert([
            'id' => 201,
            'asset_sr' => 'SRV-PE-999',
            'asset_tag' => 'TAG-SRV-201',
            'description' => 'Dell PowerEdge R740 Primary DB',
            'location' => 'Server Room Rack 1',
            'covered' => '24x7 Platinum Support',
            'purchase_date' => '2023-01-10',
            'purchase_cost' => 450000,
            'end_of_sale' => '2026-01-10',
            'end_of_support' => '2028-01-10',
            'contract_expiry' => '2027-01-10',
            'amc_cost' => 45000,
            'contract_type' => 'Comprehensive AMC',
            'contracthash' => 'CTR-HASH-9988',
            'address' => 'Floor 3, Datacenter',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'country' => 'India',
            'terms' => 'Standard SLA 4hr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seed legacy devices (switches)
        DB::connection('legacy')->table('devices')->insert([
            'id' => 301,
            'oem' => 'Cisco',
            'partcode' => 'WS-C2960X-48TD-L',
            'serial' => 'FCW1947C01A',
            'location' => 'HQ Building',
            'sublocation' => 'Floor 2 Network Rack',
            'purchase_date' => '2023-06-20',
            'cost' => 120000,
            'support_date' => '2027-06-20',
            'amc_start' => '2024-06-20',
            'amc_end' => '2025-06-20',
            'amc_cost' => 12000,
            'remark' => 'Core Switch',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Seed legacy storages
        DB::connection('legacy')->table('storages')->insert([
            'id' => 401,
            'description' => 'Synology DiskStation DS1821+',
            'serial' => 'SYN-DS-401',
            'warranty_end' => '2026-12-31',
            'location' => 'Backup Room',
            'type' => 'NAS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: false);

        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isDryRun());

        // Verify desktop mapping
        $desktopAsset = Asset::where('legacy_source', 'desktop')->where('legacy_id', 101)->first();
        $this->assertNotNull($desktopAsset);
        $this->assertEquals(AssetType::DESKTOP, $desktopAsset->asset_type);
        $this->assertEquals('SN-DESK-01', $desktopAsset->serial_number);
        $this->assertEquals('i7-12700', $desktopAsset->specifications['processor']);
        $this->assertEquals('16GB', $desktopAsset->specifications['ram']);
        $this->assertEquals('512GB SSD', $desktopAsset->specifications['hdd']);
        $this->assertArrayHasKey('processor', $desktopAsset->legacy_payload);
        $this->assertEquals('HP', $desktopAsset->manufacturer_name_legacy);

        // Verify server mapping
        $serverAsset = Asset::where('legacy_source', 'server')->where('legacy_id', 201)->first();
        $this->assertNotNull($serverAsset);
        $this->assertEquals(AssetType::SERVER, $serverAsset->asset_type);
        $this->assertEquals('SRV-PE-999', $serverAsset->serial_number);
        $this->assertEquals('TAG-SRV-201', $serverAsset->asset_tag);
        $this->assertEquals('24x7 Platinum Support', $serverAsset->specifications['support_covered']);
        $this->assertEquals('New Delhi', $serverAsset->legacy_payload['city']);

        // Verify device (switch) mapping
        $deviceAsset = Asset::where('legacy_source', 'device')->where('legacy_id', 301)->first();
        $this->assertNotNull($deviceAsset);
        $this->assertEquals(AssetType::SWITCH, $deviceAsset->asset_type);
        $this->assertEquals('WS-C2960X-48TD-L', $deviceAsset->part_code);
        $this->assertEquals('FCW1947C01A', $deviceAsset->serial_number);
        $this->assertEquals('Cisco', $deviceAsset->manufacturer_name_legacy);

        // Verify storage mapping
        $storageAsset = Asset::where('legacy_source', 'storage')->where('legacy_id', 401)->first();
        $this->assertNotNull($storageAsset);
        $this->assertEquals(AssetType::STORAGE, $storageAsset->asset_type);
        $this->assertEquals('SYN-DS-401', $storageAsset->serial_number);
        $this->assertEquals('NAS', $storageAsset->specifications['storage_type']);
    }

    /**
     * Test importer creates asset assignments for laptops with assigned officials.
     */
    public function test_importer_creates_asset_assignments_for_assigned_laptops(): void
    {
        // Create an official in target / legacy
        $official = Official::create([
            'id' => 50,
            'name' => 'Alice Johnson',
            'title' => 'Ms.',
            'designation' => 'Senior Developer',
            'email' => 'alice@example.com',
        ]);

        // Seed legacy laptop assigned to official 50
        DB::connection('legacy')->table('laptops')->insert([
            'id' => 501,
            'serial' => 'SN-LAP-501',
            'brand' => 'Lenovo',
            'category' => 'ThinkPad T14',
            'official_id' => 50,
            'active' => 1,
            'file' => 'PO-2024-05',
            'purchased' => '2024-02-01',
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: false);

        $this->assertTrue($result->isSuccessful());

        $laptopAsset = Asset::where('legacy_source', 'laptop')->where('legacy_id', 501)->first();
        $this->assertNotNull($laptopAsset);
        $this->assertEquals(AssetType::LAPTOP, $laptopAsset->asset_type);
        $this->assertEquals(AssetStatus::IN_USE, $laptopAsset->status);
        $this->assertEquals(50, $laptopAsset->assigned_official_id);

        // Verify AssetAssignment record created
        $assignment = AssetAssignment::where('asset_id', $laptopAsset->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals(50, $assignment->official_id);
        $this->assertNull($assignment->returned_at);
        $this->assertEquals('legacy_import', $assignment->source);
    }

    /**
     * Test importer maps consumables, stock ledger entries, and verifies stock balances.
     */
    public function test_importer_maps_consumables_and_stock_entries_verifying_ledger(): void
    {
        // Seed legacy consumables
        DB::connection('legacy')->table('consumables')->insert([
            'id' => 601,
            'name' => 'Logitech Wireless Mouse M185',
            'in_stock' => 25,
            'min_quantity' => 5,
            'max_quantity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed legacy entries
        DB::connection('legacy')->table('entries')->insert([
            'id' => 701,
            'consumable_id' => 601,
            'type' => 1, // Purchase / inbound
            'amount' => 30,
            'stock' => 30,
            'issuer_id' => 1, // Not existing official, will be recorded as anomaly
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        DB::connection('legacy')->table('entries')->insert([
            'id' => 702,
            'consumable_id' => 601,
            'type' => 2, // Issue / outbound
            'amount' => 5,
            'stock' => 25,
            'issuer_id' => 1,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: false);

        $this->assertTrue($result->isSuccessful());

        $consumable = Consumable::find(601);
        $this->assertNotNull($consumable);
        $this->assertEquals(25, $consumable->in_stock);
        $this->assertEquals(5, $consumable->min_quantity);

        $entry1 = Entry::where('legacy_id', 701)->first();
        $this->assertNotNull($entry1);
        $this->assertEquals(StockEntryType::PURCHASE, $entry1->type);
        $this->assertEquals(30, $entry1->quantity);
        $this->assertEquals(30, $entry1->stock_after);

        $entry2 = Entry::where('legacy_id', 702)->first();
        $this->assertNotNull($entry2);
        $this->assertEquals(StockEntryType::ISSUE, $entry2->type);
        $this->assertEquals(5, $entry2->quantity);
        $this->assertEquals(25, $entry2->stock_after);
    }

    /**
     * Test importer maps agreements and payments with deterministic schedule keys.
     */
    public function test_importer_maps_agreements_and_payments(): void
    {
        // Seed legacy agreement
        DB::connection('legacy')->table('agreements')->insert([
            'id' => 801,
            'name' => 'Data Center Precision AC AMC',
            'agency' => 'Schneider Electric',
            'file_id' => 1,
            'type' => 'AMC',
            'expiry' => '2027-03-31',
            'annual_cost' => 240000,
            'frequency' => 3, // Quarterly
            'paid_till' => '2026-06-30',
            'remarks' => '24/7 breakdown call support',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed legacy payment
        DB::connection('legacy')->table('payments')->insert([
            'id' => 901,
            'name' => 'Q1 Payment 2026',
            'agreement_id' => 801,
            'due_date' => '2026-03-31',
            'amount' => 60000,
            'remark' => 'Paid via NEFT',
            'pending' => 0, // Completed
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: false);

        $this->assertTrue($result->isSuccessful());

        $agreement = Agreement::find(801);
        $this->assertNotNull($agreement);
        $this->assertEquals('Data Center Precision AC AMC', $agreement->name);
        $this->assertEquals(240000.00, $agreement->annual_cost);
        $this->assertEquals(3, $agreement->billing_interval_months);
        $this->assertArrayHasKey('agency', $agreement->legacy_payload);

        $payment = Payment::where('legacy_id', 901)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(801, $payment->agreement_id);
        $this->assertEquals(60000.00, $payment->amount);
        $this->assertEquals(PaymentStatus::COMPLETED, $payment->status);
        $this->assertEquals('legacy-payment-901', $payment->schedule_key);
    }

    /**
     * Test dry-run mode leaves target database empty without writing changes.
     */
    public function test_dry_run_leaves_target_database_empty(): void
    {
        // Seed simulated legacy rows
        DB::connection('legacy')->table('desktops')->insert([
            'id' => 1001,
            'serial' => 'SN-DRY-01',
            'brand' => 'Dell',
            'category' => 'OptiPlex',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('legacy')->table('agreements')->insert([
            'id' => 1002,
            'name' => 'Dry Run Agreement',
            'agency' => 'Test Vendor',
            'file_id' => 1,
            'type' => 'SLA',
            'annual_cost' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: true);

        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->isDryRun());
        $this->assertArrayHasKey('assets_desktop', $result->counts);

        // Verify target database is clean and empty
        $this->assertEquals(0, Asset::count());
        $this->assertEquals(0, Agreement::count());
        $this->assertEquals(0, AssetAssignment::count());
    }

    /**
     * Test reconciliation reporter generates report with counts, financial parity, and anomalies.
     */
    public function test_reconciliation_reporter_detects_count_parity_and_anomalies(): void
    {
        // Seed legacy data
        DB::connection('legacy')->table('desktops')->insert([
            'id' => 1101,
            'serial' => 'SN-REC-01',
            'brand' => 'Lenovo',
            'category' => 'ThinkCentre',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('legacy')->table('agreements')->insert([
            'id' => 1102,
            'name' => 'Recon Agreement',
            'agency' => 'Vendor A',
            'file_id' => 1,
            'type' => 'AMC',
            'annual_cost' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manager = app(LegacyImportManager::class);
        $manager->import(dryRun: false);

        $reporter = app(ReconciliationReporter::class);
        $report = $reporter->generateReport();

        $this->assertArrayHasKey('counts', $report);
        $this->assertArrayHasKey('financial_parity', $report);
        $this->assertArrayHasKey('stock_discrepancies', $report);
        $this->assertArrayHasKey('is_clean', $report);

        $this->assertEquals(1, $report['counts']['entities']['desktops']['legacy']);
        $this->assertEquals(1, $report['counts']['entities']['desktops']['target']);
        $this->assertTrue($report['counts']['entities']['desktops']['match']);
        $this->assertEquals(100000.00, $report['financial_parity']['target_agreements_total']);
    }

    /**
     * Test CLI command execution in dry-run, live, and verify-only modes.
     */
    public function test_cli_command_executes_import_and_verify_modes(): void
    {
        // Seed legacy desktop
        DB::connection('legacy')->table('desktops')->insert([
            'id' => 1201,
            'serial' => 'SN-CLI-01',
            'brand' => 'HP',
            'category' => 'ProDesk',
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Test verify mode on empty target
        $verifyCode = Artisan::call('inventory:import-legacy', ['--verify' => true]);
        $this->assertEquals(0, $verifyCode);

        // 2. Test dry-run mode
        $dryRunCode = Artisan::call('inventory:import-legacy', ['--dry-run' => true]);
        $this->assertEquals(0, $dryRunCode);
        $this->assertEquals(0, Asset::count());

        // 3. Test live import mode
        $liveCode = Artisan::call('inventory:import-legacy');
        $this->assertEquals(0, $liveCode);
        $this->assertEquals(1, Asset::where('legacy_source', 'desktop')->where('legacy_id', 1201)->count());

        // 4. Test post-import verify mode
        $postVerifyCode = Artisan::call('inventory:import-legacy', ['--verify' => true]);
        $this->assertEquals(0, $postVerifyCode);
    }
}
