<?php

namespace Tests\Feature;

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
use App\Models\OrganizationalUnit;
use App\Models\Payment;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendViewRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inventory.poc_ui_mode' => false]);
    }

    /**
     * Test guest login view renders clean form and components.
     */
    public function test_login_view_renders_clean_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('NDC Inventory Management');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
        $response->assertSee('Keep me signed in');
        $response->assertSee('Sign In to Portal');
    }

    /**
     * Test modern dashboard renders all KPI counters, action center, and live activity stream.
     */
    public function test_dashboard_renders_all_kpi_counters_and_activity_feed(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@ndc.test',
            'role' => UserRole::ADMIN,
        ]);
        [$unit] = $this->inventoryContext($admin, 'DASHBOARD');

        $official = Official::factory()->create(['name' => 'Dr. Sharma']);

        // Create sample data for KPIs
        Asset::factory()->laptop()->create([
            'name' => 'ThinkPad T14s',
            'status' => AssetStatus::IN_USE,
            'assigned_official_id' => $official->id,
            'organizational_unit_id' => $unit->id,
        ]);
        Asset::factory()->server()->create([
            'name' => 'PowerEdge R750',
            'status' => AssetStatus::IN_STOCK,
            'organizational_unit_id' => $unit->id,
        ]);

        Consumable::factory()->create([
            'name' => 'Cat6 Patch Cord',
            'in_stock' => 2,
            'min_quantity' => 10, // Low stock
        ]);

        $agreement = Agreement::factory()->create([
            'organizational_unit_id' => $unit->id,
            'name' => 'Data Center UPS AMC',
            'agency' => 'Schneider Electric',
            'annual_cost' => 150000.00,
            'expiry' => now()->addDays(20), // Expiring in 30 days
        ]);

        Payment::factory()->create([
            'agreement_id' => $agreement->id,
            'amount' => 37500.00,
            'due_date' => now()->subDays(5),
            'status' => PaymentStatus::PENDING, // Overdue
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Enterprise Operations Dashboard');
        $response->assertSee('Total IT Assets');
        $response->assertSee('Expiring Agreements');
        $response->assertSee('Low Stock Consumables');
        $response->assertSee('Payment Obligations');
        $response->assertSee('Hardware Fleet Breakdown');
        $response->assertSee('Action Center & Urgent Tasks');
        $response->assertSee('Live Activity Stream');
        $response->assertDontSee('No recent audit activity records found.');
        $response->assertSee('Asset');
    }

    /**
     * Test asset index view renders assets table, type filters, and status badges.
     */
    public function test_asset_index_renders_table_type_chips_and_status_badges(): void
    {
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        [$unit, $site] = $this->inventoryContext($user, 'INDEX');
        $location = Location::factory()->create([
            'name' => 'Rack 4B',
            'site_id' => $site->id,
        ]);
        $official = Official::factory()->create([
            'name' => 'Priya Engineer',
            'location_id' => $location->id,
        ]);

        $laptop = Asset::factory()->create([
            'name' => 'MacBook Pro 16 M3',
            'asset_type' => AssetType::LAPTOP,
            'asset_tag' => 'TAG-MBP-99',
            'serial_number' => 'C02XYZ1234',
            'status' => AssetStatus::IN_USE,
            'location_id' => $location->id,
            'assigned_official_id' => $official->id,
            'organizational_unit_id' => $unit->id,
        ]);

        $server = Asset::factory()->create([
            'name' => 'HPE ProLiant DL380',
            'asset_type' => AssetType::SERVER,
            'asset_tag' => 'TAG-HPE-01',
            'serial_number' => 'SGH123456',
            'status' => AssetStatus::IN_STOCK,
            'location_id' => $location->id,
            'organizational_unit_id' => $unit->id,
        ]);

        // 1. All assets
        $response = $this->actingAs($user)->get(route('assets.index'));
        $response->assertStatus(200);
        $response->assertSee('IT Hardware Fleet');
        $response->assertSee('All Assets');
        $response->assertSee('Laptops');
        $response->assertSee('Servers');
        $response->assertSee('MacBook Pro 16 M3');
        $response->assertSee('TAG-MBP-99');
        $response->assertSee('Priya Engineer');
        $response->assertSee('HPE ProLiant DL380');
        $response->assertSee('In Use');
        $response->assertSee('In Stock');

        // 2. Filtered by type=laptop
        $filteredResponse = $this->actingAs($user)->get(route('assets.index', ['type' => 'laptop']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee('MacBook Pro 16 M3');
        $filteredResponse->assertDontSee('HPE ProLiant DL380');
    }

    /**
     * Test asset show view renders hardware specifications, assignment history, and action modals.
     */
    public function test_asset_show_renders_specifications_timeline_and_modals(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        [$unit, $site] = $this->inventoryContext($user, 'SHOW');
        $manufacturer = Manufacturer::factory()->create(['name' => 'Dell Technologies']);
        $location = Location::factory()->create([
            'name' => 'NOC Room',
            'site_id' => $site->id,
        ]);
        $official = Official::factory()->create([
            'name' => 'Rajesh Officer',
            'location_id' => $location->id,
        ]);

        /** @var Asset $asset */
        $asset = Asset::factory()->create([
            'name' => 'Dell Precision 5820 Workstation',
            'asset_type' => AssetType::DESKTOP,
            'asset_tag' => 'NDC-WS-5820',
            'serial_number' => 'SN-PREC-5820',
            'ip_address' => '10.20.30.40',
            'mac_address' => '00:14:22:01:23:45',
            'operating_system' => 'Ubuntu 24.04 LTS',
            'manufacturer_id' => $manufacturer->id,
            'location_id' => $location->id,
            'assigned_official_id' => $official->id,
            'status' => AssetStatus::IN_USE,
            'purchase_cost' => 125000.00,
            'organizational_unit_id' => $unit->id,
        ]);

        AssetAssignment::create([
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'assigned_by' => $user->id,
            'assigned_at' => now()->subMonths(2),
            'remarks' => 'Deployment for GIS data processing',
        ]);

        $response = $this->actingAs($user)->get(route('assets.show', $asset));

        $response->assertStatus(200);
        $response->assertSee('Dell Precision 5820 Workstation');
        $response->assertSee('NDC-WS-5820');
        $response->assertSee('Dell Technologies');
        $response->assertSee('SN-PREC-5820');
        $response->assertSee('10.20.30.40');
        $response->assertSee('Ubuntu 24.04 LTS');
        $response->assertSee('Rajesh Officer');
        $response->assertSee('Assignment & Custody History');
        $response->assertSee('Deployment for GIS data processing');
        $response->assertSee('Return to Stock');
        $response->assertSee('Decommission');
    }

    /**
     * Test asset create and edit forms render required inputs.
     */
    public function test_asset_create_and_edit_forms_render(): void
    {
        $manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        [$unit] = $this->inventoryContext($manager, 'FORM');
        $asset = Asset::factory()->create([
            'name' => 'Form Test Asset',
            'organizational_unit_id' => $unit->id,
        ]);

        $createResponse = $this->actingAs($manager)->get(route('assets.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Add New IT Asset');
        $createResponse->assertSee('Core Identification');
        $createResponse->assertSee('Save & Register Asset');

        $editResponse = $this->actingAs($manager)->get(route('assets.edit', $asset));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Asset: Form Test Asset');
        $editResponse->assertSee('Save Changes');
    }

    /**
     * Test consumable index renders stock levels, progress indicators, and low stock flags.
     */
    public function test_consumable_index_renders_stock_levels_and_warnings(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);

        Consumable::factory()->create([
            'name' => 'HDMI Cable 3m',
            'sku' => 'CAB-HDMI-03M',
            'in_stock' => 15,
            'min_quantity' => 5,
            'max_quantity' => 50,
        ]);

        Consumable::factory()->create([
            'name' => 'Wireless Mouse',
            'sku' => 'PER-MOU-01',
            'in_stock' => 2,
            'min_quantity' => 8, // Low stock
            'max_quantity' => 30,
        ]);

        $response = $this->actingAs($user)->get(route('consumables.index'));

        $response->assertStatus(200);
        $response->assertSee('Consumables & Supplies');
        $response->assertSee('HDMI Cable 3m');
        $response->assertSee('CAB-HDMI-03M');
        $response->assertSee('Wireless Mouse');
        $response->assertSee('Low Stock');
    }

    /**
     * Test consumable show view renders metrics, transaction modal, and ledger history.
     */
    public function test_consumable_show_renders_metrics_and_transactions(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        [, $site] = $this->inventoryContext($user, 'CONSUMABLE-SHOW');
        $location = Location::factory()->create(['site_id' => $site->id]);
        $official = Official::factory()->create([
            'name' => 'Anil Verma',
            'location_id' => $location->id,
        ]);

        /** @var Consumable $consumable */
        $consumable = Consumable::factory()->create([
            'name' => 'Cat6 RJ45 Connectors Box',
            'sku' => 'CON-RJ45-100',
            'unit' => 'box',
            'in_stock' => 8,
            'min_quantity' => 3,
            'max_quantity' => 20,
        ]);

        Entry::create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE,
            'quantity' => 10,
            'stock_after' => 10,
            'recorded_by' => $user->id,
            'remarks' => 'Initial bulk order',
        ]);

        Entry::create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::ISSUE,
            'quantity' => 2,
            'stock_after' => 8,
            'recipient_official_id' => $official->id,
            'recorded_by' => $user->id,
            'remarks' => 'Issued for 3rd floor cabling',
        ]);

        $response = $this->actingAs($user)->get(route('consumables.show', $consumable));

        $response->assertStatus(200);
        $response->assertSee('Cat6 RJ45 Connectors Box');
        $response->assertSee('CON-RJ45-100');
        $response->assertSee('Current Inventory Balance');
        $response->assertSee('8');
        $response->assertSee('Initial bulk order');
        $response->assertSee('Issued for 3rd floor cabling');
        $response->assertSee('Anil Verma');
    }

    /**
     * Test stock ledger index renders immutable ledger entries and filters.
     */
    public function test_stock_ledger_index_renders_immutable_entries(): void
    {
        $user = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        [, $site] = $this->inventoryContext($user, 'STOCK-LEDGER');
        $location = Location::factory()->create(['site_id' => $site->id]);
        $official = Official::factory()->create([
            'name' => 'Kavita Officer',
            'location_id' => $location->id,
        ]);
        $consumable = Consumable::factory()->create(['name' => 'SFP+ Fiber Transceiver']);

        Entry::create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE,
            'quantity' => 25,
            'stock_after' => 25,
            'recorded_by' => $user->id,
            'remarks' => 'Batch PO-7711',
        ]);

        Entry::create([
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::ISSUE,
            'quantity' => 5,
            'stock_after' => 20,
            'recipient_official_id' => $official->id,
            'recorded_by' => $user->id,
            'remarks' => 'Deployed for core router interconnect',
        ]);

        $response = $this->actingAs($user)->get(route('stock.index'));

        $response->assertStatus(200);
        $response->assertSee('Immutable Stock Ledger');
        $response->assertSee('SFP+ Fiber Transceiver');
        $response->assertSee('Purchase');
        $response->assertSee('Issue');
        $response->assertSee('Batch PO-7711');
        $response->assertSee('Kavita Officer');
    }

    /**
     * Test agreement index and show views render contracts and payment schedules.
     */
    public function test_agreements_index_and_show_views_render(): void
    {
        $user = User::factory()->create(['role' => UserRole::FINANCE_OPERATOR]);
        [$unit] = $this->inventoryContext($user, 'AGREEMENTS');

        /** @var Agreement $agreement */
        $agreement = Agreement::factory()->create([
            'organizational_unit_id' => $unit->id,
            'name' => 'Enterprise Cloud Backup License',
            'agency' => 'Veeam Software',
            'type' => 'Software License',
            'annual_cost' => 240000.00,
            'currency' => 'INR',
            'billing_interval_months' => 3,
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
        ]);

        Payment::create([
            'agreement_id' => $agreement->id,
            'schedule_key' => '2026-Q1',
            'amount' => 60000.00,
            'currency' => 'INR',
            'due_date' => '2026-01-01',
            'status' => PaymentStatus::COMPLETED,
            'invoice_number' => 'INV-VEEAM-01',
            'paid_date' => '2026-01-05',
        ]);

        Payment::create([
            'agreement_id' => $agreement->id,
            'schedule_key' => '2026-Q2',
            'amount' => 60000.00,
            'currency' => 'INR',
            'due_date' => '2026-04-01',
            'status' => PaymentStatus::PENDING,
        ]);

        // 1. Agreements Index
        $indexResponse = $this->actingAs($user)->get(route('agreements.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Vendor Agreements & AMC');
        $indexResponse->assertSee('Enterprise Cloud Backup License');
        $indexResponse->assertSee('Veeam Software');

        // 2. Agreements Show
        $showResponse = $this->actingAs($user)->get(route('agreements.show', $agreement));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Enterprise Cloud Backup License');
        $showResponse->assertSee('Financial Terms');
        $showResponse->assertSee('Scheduled Payment Milestones');
        $showResponse->assertSee('2026-Q1');
        $showResponse->assertSee('INV-VEEAM-01');
        $showResponse->assertSee('2026-Q2');
        $showResponse->assertSee('Mark as Paid');
    }

    /**
     * Test payments index view renders scheduled payments and status filters.
     */
    public function test_payments_index_view_renders_with_filters(): void
    {
        $user = User::factory()->create(['role' => UserRole::FINANCE_OPERATOR]);
        [$unit] = $this->inventoryContext($user, 'PAYMENTS');
        $agreement = Agreement::factory()->create([
            'name' => 'Storage SAN AMC',
            'organizational_unit_id' => $unit->id,
        ]);

        Payment::create([
            'agreement_id' => $agreement->id,
            'schedule_key' => 'SAN-AMC-M1',
            'amount' => 50000.00,
            'due_date' => now()->subDays(10),
            'status' => PaymentStatus::PENDING, // Overdue
        ]);

        Payment::create([
            'agreement_id' => $agreement->id,
            'schedule_key' => 'SAN-AMC-M2',
            'amount' => 50000.00,
            'due_date' => now()->addDays(30),
            'status' => PaymentStatus::PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('payments.index'));
        $response->assertStatus(200);
        $response->assertSee('Scheduled Vendor Payments');
        $response->assertSee('Storage SAN AMC');
        $response->assertSee('SAN-AMC-M1');
        $response->assertSee('Overdue');

        $overdueResponse = $this->actingAs($user)->get(route('payments.index', ['status' => 'overdue']));
        $overdueResponse->assertStatus(200);
        $overdueResponse->assertSee('SAN-AMC-M1');
    }

    /**
     * @return array{0: OrganizationalUnit, 1: Site}
     */
    private function inventoryContext(User $user, string $code): array
    {
        $unit = OrganizationalUnit::factory()->create(['code' => "UI-{$code}"]);
        $user->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        $site = Site::create([
            'code' => "UI-SITE-{$code}",
            'name' => "UI Site {$code}",
            'is_active' => true,
        ]);
        $site->organizationalUnits()->attach($unit->id);

        return [$unit, $site];
    }
}
