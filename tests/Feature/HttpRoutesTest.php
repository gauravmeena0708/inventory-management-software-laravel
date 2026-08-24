<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\PaymentStatus;
use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated guest is redirected to login.
     */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test authenticated admin can access dashboard and receives computed KPIs.
     */
    public function test_authenticated_admin_can_access_dashboard_and_kpis(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        // Create some sample data
        Asset::factory()->laptop()->inStock()->create();
        Asset::factory()->server()->inUse()->create();
        Consumable::factory()->create(['in_stock' => 1, 'min_quantity' => 5]); // low stock

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Test json response of dashboard KPIs
        $jsonResponse = $this->actingAs($admin)->getJson(route('dashboard'));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'total_assets',
            'assets_in_use',
            'assets_in_stock',
            'low_stock_consumables',
            'pending_payments',
            'overdue_payments',
        ]);
        $this->assertEquals(2, $jsonResponse->json('total_assets'));
        $this->assertEquals(1, $jsonResponse->json('low_stock_consumables'));
    }

    /**
     * Test asset creation, filtering by type, assignment, and export endpoints.
     */
    public function test_asset_lifecycle_filtering_and_export_endpoints(): void
    {
        $manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $location = Location::factory()->create(['name' => 'HQ Floor 2']);
        $manufacturer = Manufacturer::factory()->create(['name' => 'Dell']);
        $official = Official::factory()->create(['name' => 'Bob Engineer']);

        // 1. Create asset via POST /assets
        $createResponse = $this->actingAs($manager)->post(route('assets.store'), [
            'name' => 'Dell Latitude 7420',
            'asset_type' => AssetType::LAPTOP->value,
            'serial_number' => 'DL-LAT-7420',
            'asset_tag' => 'TAG-LAT-01',
            'location_id' => $location->id,
            'manufacturer_id' => $manufacturer->id,
            'purchase_date' => '2026-02-01',
            'purchase_cost' => 75000,
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('assets', [
            'name' => 'Dell Latitude 7420',
            'serial_number' => 'DL-LAT-7420',
            'asset_type' => AssetType::LAPTOP->value,
            'status' => AssetStatus::IN_STOCK->value,
        ]);

        /** @var Asset $asset */
        $asset = Asset::where('serial_number', 'DL-LAT-7420')->firstOrFail();

        // 2. Filter assets by type
        $filterResponse = $this->actingAs($manager)->get(route('assets.index', ['type' => 'laptop']));
        $filterResponse->assertStatus(200);

        // 3. Assign asset to official via POST /assets/{asset}/assign
        $assignResponse = $this->actingAs($manager)->post(route('assets.assign', $asset), [
            'official_id' => $official->id,
            'remarks' => 'Deployment for new joiner',
            'condition_out' => 'Excellent',
        ]);

        $assignResponse->assertRedirect();
        $this->assertEquals(AssetStatus::IN_USE, $asset->fresh()->status);
        $this->assertEquals($official->id, $asset->fresh()->assigned_official_id);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'returned_at' => null,
        ]);

        // 4. Return asset via POST /assets/{asset}/return
        $returnResponse = $this->actingAs($manager)->post(route('assets.return', $asset), [
            'remarks' => 'Returned after project',
            'condition_in' => 'Good condition',
        ]);

        $returnResponse->assertRedirect();
        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertNull($asset->fresh()->assigned_official_id);

        // 5. Decommission asset via PATCH /assets/{asset}/decommission
        $decomResponse = $this->actingAs($manager)->patch(route('assets.decommission', $asset), [
            'reason' => 'End of lifecycle replacement',
        ]);

        $decomResponse->assertRedirect();
        $this->assertEquals(AssetStatus::DECOMMISSIONED, $asset->fresh()->status);

        // 6. Export assets endpoint
        $exportResponse = $this->actingAs($manager)->get(route('assets.export'));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('application/', $exportResponse->headers->get('content-type') ?? '');
    }

    /**
     * Test consumable creation and stock ledger entries (purchase and issue).
     */
    public function test_consumable_creation_and_stock_issuance(): void
    {
        $stockOp = User::factory()->create(['role' => UserRole::STOCK_OPERATOR]);
        $manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $official = Official::factory()->create(['name' => 'Sarah Developer']);

        // 1. Manager creates consumable
        $createResponse = $this->actingAs($manager)->post(route('consumables.store'), [
            'name' => 'USB-C Display Cable',
            'unit' => 'piece',
            'in_stock' => 0,
            'min_quantity' => 5,
        ]);

        $createResponse->assertRedirect();
        /** @var Consumable $consumable */
        $consumable = Consumable::where('name', 'USB-C Display Cable')->firstOrFail();

        // 2. Stock Operator records purchase entry
        $purchaseResponse = $this->actingAs($stockOp)->post(route('consumables.entries.store', $consumable), [
            'type' => StockEntryType::PURCHASE->value,
            'quantity' => 20,
            'remarks' => 'Batch PO-8877',
            'idempotency_key' => 'PURCHASE-PO-8877',
        ]);

        $purchaseResponse->assertRedirect();
        $this->assertEquals(20, $consumable->fresh()->in_stock);
        $this->assertDatabaseHas('entries', [
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE->value,
            'quantity' => 20,
            'stock_after' => 20,
        ]);

        // 3. Stock Operator records issue entry to official
        $issueResponse = $this->actingAs($stockOp)->post(route('consumables.entries.store', $consumable), [
            'type' => StockEntryType::ISSUE->value,
            'quantity' => 3,
            'recipient_official_id' => $official->id,
            'remarks' => 'Issued for workstation setup',
        ]);

        $issueResponse->assertRedirect();
        $this->assertEquals(17, $consumable->fresh()->in_stock);
        $this->assertDatabaseHas('entries', [
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::ISSUE->value,
            'quantity' => 3,
            'stock_after' => 17,
            'recipient_official_id' => $official->id,
        ]);

        // 4. Stock ledger listing
        $stockIndex = $this->actingAs($stockOp)->get(route('stock.index'));
        $stockIndex->assertStatus(200);
    }

    /**
     * Test agreement creation, automatic payment schedule generation, and payment completion.
     */
    public function test_agreement_creation_and_payment_completion(): void
    {
        $finOp = User::factory()->create(['role' => UserRole::FINANCE_OPERATOR]);

        // 1. Finance Operator creates Agreement
        $agreementResponse = $this->actingAs($finOp)->post(route('agreements.store'), [
            'name' => 'Firewall Support & AMC 2026',
            'agency' => 'Fortinet Inc',
            'type' => 'Service',
            'annual_cost' => 120000.00,
            'currency' => 'INR',
            'billing_interval_months' => 3, // Quarterly
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
        ]);

        $agreementResponse->assertRedirect();
        /** @var Agreement $agreement */
        $agreement = Agreement::where('name', 'Firewall Support & AMC 2026')->firstOrFail();

        // Check that 4 quarterly payments were automatically generated
        $this->assertCount(4, $agreement->payments);
        /** @var Payment $firstPayment */
        $firstPayment = $agreement->payments()->orderBy('due_date')->firstOrFail();
        $this->assertEquals(30000.00, $firstPayment->amount);
        $this->assertEquals(PaymentStatus::PENDING, $firstPayment->status);

        // 2. Mark first payment as completed via POST /payments/{payment}/complete
        $completeResponse = $this->actingAs($finOp)->post(route('payments.complete', $firstPayment), [
            'invoice_number' => 'INV-FORTI-2026-01',
            'paid_date' => '2026-01-05',
        ]);

        $completeResponse->assertRedirect();
        $this->assertEquals(PaymentStatus::COMPLETED, $firstPayment->fresh()->status);
        $this->assertEquals('INV-FORTI-2026-01', $firstPayment->fresh()->invoice_number);
        $this->assertEquals($finOp->id, $firstPayment->fresh()->completed_by);

        // Agreement paid_till should be updated
        $this->assertNotNull($agreement->fresh()->paid_till);

        // 3. Payments list & export
        $paymentsResponse = $this->actingAs($finOp)->get(route('payments.index', ['status' => 'completed']));
        $paymentsResponse->assertStatus(200);

        $agreementsExportResponse = $this->actingAs($finOp)->get(route('agreements.export'));
        $agreementsExportResponse->assertStatus(200);
    }

    /**
     * Test unauthorized roles receive 403 Forbidden on protected mutating endpoints.
     */
    public function test_unauthorized_role_receives_403_on_protected_endpoints(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        $asset = Asset::factory()->laptop()->inStock()->create();
        $consumable = Consumable::factory()->create(['in_stock' => 10]);
        $agreement = Agreement::factory()->create();
        $payment = Payment::factory()->create(['agreement_id' => $agreement->id, 'status' => PaymentStatus::PENDING]);
        $official = Official::factory()->create();

        // 1. Viewer cannot create asset
        $this->actingAs($viewer)
            ->post(route('assets.store'), [
                'name' => 'Unauthorized Laptop',
                'asset_type' => 'laptop',
            ])
            ->assertStatus(403);

        // 2. Viewer cannot assign asset
        $this->actingAs($viewer)
            ->post(route('assets.assign', $asset), [
                'official_id' => $official->id,
            ])
            ->assertStatus(403);

        // 3. Viewer cannot create consumable
        $this->actingAs($viewer)
            ->post(route('consumables.store'), [
                'name' => 'Unauthorized Consumable',
            ])
            ->assertStatus(403);

        // 4. Viewer cannot post stock entry
        $this->actingAs($viewer)
            ->post(route('consumables.entries.store', $consumable), [
                'type' => StockEntryType::PURCHASE->value,
                'quantity' => 10,
            ])
            ->assertStatus(403);

        // 5. Viewer cannot create agreement
        $this->actingAs($viewer)
            ->post(route('agreements.store'), [
                'name' => 'Unauthorized Agreement',
                'agency' => 'Some Agency',
                'type' => 'Service',
                'billing_interval_months' => 1,
                'billing_anchor_date' => '2026-01-01',
                'expiry' => '2026-12-31',
            ])
            ->assertStatus(403);

        // 6. Viewer cannot complete payment
        $this->actingAs($viewer)
            ->post(route('payments.complete', $payment), [
                'invoice_number' => 'INV-TEST',
            ])
            ->assertStatus(403);

        // 7. Viewer can still view index pages (read-only)
        $this->actingAs($viewer)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($viewer)->get(route('assets.index'))->assertStatus(200);
        $this->actingAs($viewer)->get(route('consumables.index'))->assertStatus(200);
        $this->actingAs($viewer)->get(route('agreements.index'))->assertStatus(200);
        $this->actingAs($viewer)->get(route('payments.index'))->assertStatus(200);
    }
}
