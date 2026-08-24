<?php

namespace Tests\Feature;

use App\Contracts\TabularExporter;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\PaymentStatus;
use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Exceptions\InsufficientStockException;
use App\Exports\AgreementsExport;
use App\Exports\AssetsExport;
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
use App\Services\Assets\ReturnAssetAction;
use App\Services\Inventory\PostStockEntryAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class EndToEndInventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the entire unified inventory management lifecycle end-to-end:
     * 1. Multi-role authentication & capability matrix.
     * 2. Master data setup (Locations, Manufacturers, Officials).
     * 3. Asset creation -> Assignment to official -> Condition-verified return -> Decommission.
     * 4. Consumable creation -> Stock purchase -> Stock issue with atomic ledger tracking.
     * 5. Agreement creation -> Idempotent schedule generation -> Milestone payment completion & paid_till sync.
     * 6. Tabular exports (AssetsExport, AgreementsExport) and activity audit logging.
     */
    public function test_complete_unified_inventory_lifecycle_end_to_end(): void
    {
        // -------------------------------------------------------------------------
        // 1. Role Setup & Permission Verification
        // -------------------------------------------------------------------------
        /** @var User $admin */
        $admin = User::factory()->admin()->create([
            'name' => 'System Admin',
            'email' => 'admin@inventory.local',
        ]);

        /** @var User $inventoryManager */
        $inventoryManager = User::factory()->inventoryManager()->create([
            'name' => 'Alice Manager',
            'email' => 'manager@inventory.local',
        ]);

        /** @var User $stockOperator */
        $stockOperator = User::factory()->stockOperator()->create([
            'name' => 'Bob Stockman',
            'email' => 'stock@inventory.local',
        ]);

        /** @var User $financeOperator */
        $financeOperator = User::factory()->financeOperator()->create([
            'name' => 'Carol Finance',
            'email' => 'finance@inventory.local',
        ]);

        /** @var User $auditor */
        $auditor = User::factory()->auditor()->create([
            'name' => 'Dave Auditor',
            'email' => 'auditor@inventory.local',
        ]);

        /** @var User $viewer */
        $viewer = User::factory()->viewer()->create([
            'name' => 'Eve Viewer',
            'email' => 'viewer@inventory.local',
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($inventoryManager->canManageInventory());
        $this->assertTrue($inventoryManager->canAssignAssets());
        $this->assertTrue($stockOperator->canPostStockEntries());
        $this->assertFalse($stockOperator->canManageAgreements());
        $this->assertTrue($financeOperator->canManageAgreements());
        $this->assertTrue($financeOperator->canManagePayments());
        $this->assertTrue($auditor->canViewAuditHistory());
        $this->assertTrue($viewer->canViewInventory());
        $this->assertFalse($viewer->canExportData());

        // -------------------------------------------------------------------------
        // 2. Master Data Setup
        // -------------------------------------------------------------------------
        $location = Location::create([
            'name' => 'HQ Server Room A',
            'building' => 'Building 1',
            'floor' => 'Ground',
            'sublocation' => 'Rack Row 3',
            'description' => 'Main on-premise datacenter facility',
        ]);

        $manufacturer = Manufacturer::create([
            'name' => 'Dell Technologies',
            'support_contact' => '+1-800-456-3355',
            'website' => 'https://www.dell.com',
            'remarks' => 'Primary hardware vendor',
        ]);

        $official = Official::create([
            'name' => 'Jane Engineer',
            'title' => 'Ms.',
            'designation' => 'Lead DevOps Architect',
            'email' => 'jane.engineer@company.local',
            'phone' => '+1-555-0199',
            'department' => 'Engineering',
            'location_id' => $location->id,
        ]);

        $this->assertDatabaseHas('locations', ['name' => 'HQ Server Room A']);
        $this->assertDatabaseHas('manufacturers', ['name' => 'Dell Technologies']);
        $this->assertDatabaseHas('officials', ['name' => 'Jane Engineer']);

        // -------------------------------------------------------------------------
        // 3. Asset Lifecycle (Create -> Assign -> Return -> Decommission)
        // -------------------------------------------------------------------------
        // 3a. Create Asset via HTTP endpoint by Inventory Manager
        $createAssetResponse = $this->actingAs($inventoryManager)->post(route('assets.store'), [
            'name' => 'PowerEdge R750 Rack Server',
            'asset_type' => AssetType::SERVER->value,
            'status' => AssetStatus::IN_STOCK->value,
            'serial_number' => 'DEL-R750-E2E-001',
            'asset_tag' => 'TAG-SRV-E2E-01',
            'model_number' => 'PE-R750',
            'manufacturer_id' => $manufacturer->id,
            'location_id' => $location->id,
            'purchase_date' => '2026-01-15',
            'purchase_cost' => 450000.00,
            'currency' => 'INR',
            'warranty_expiry' => '2029-01-15',
            'amc_end' => '2027-01-15',
            'specifications' => [
                'cpu' => 'Dual Intel Xeon Gold 6330',
                'ram' => '128GB DDR4 ECC',
                'storage' => '4x 1.92TB NVMe SSD RAID10',
            ],
            'description' => 'Core database and cluster hosting server',
        ]);

        $createAssetResponse->assertRedirect();
        $this->assertDatabaseHas('assets', [
            'serial_number' => 'DEL-R750-E2E-001',
            'asset_tag' => 'TAG-SRV-E2E-01',
            'status' => AssetStatus::IN_STOCK->value,
            'assigned_official_id' => null,
        ]);

        /** @var Asset $asset */
        $asset = Asset::where('serial_number', 'DEL-R750-E2E-001')->firstOrFail();
        $this->assertSame(AssetType::SERVER, $asset->asset_type);
        $this->assertSame(AssetStatus::IN_STOCK, $asset->status);

        // 3b. Assign Asset to Official
        $assignResponse = $this->actingAs($inventoryManager)->post(route('assets.assign', $asset), [
            'official_id' => $official->id,
            'condition_out' => 'Pristine / Factory New',
            'remarks' => 'Provisioned for Kubernetes cluster deployment',
        ]);

        $assignResponse->assertRedirect();
        $asset->refresh();
        $this->assertSame(AssetStatus::IN_USE, $asset->status);
        $this->assertEquals($official->id, $asset->assigned_official_id);

        $assignment = AssetAssignment::where('asset_id', $asset->id)
            ->where('official_id', $official->id)
            ->whereNull('returned_at')
            ->first();
        $this->assertNotNull($assignment);
        $this->assertEquals('Pristine / Factory New', $assignment->condition_out);
        $this->assertEquals($inventoryManager->id, $assignment->assigned_by);

        // 3c. Return Asset to Stock
        $returnResponse = $this->actingAs($inventoryManager)->post(route('assets.return', $asset), [
            'condition_in' => 'Good / Minor cosmetic wear',
            'remarks' => 'Cluster decommissioned, returned to rack storage',
        ]);

        $returnResponse->assertRedirect();
        $asset->refresh();
        $this->assertSame(AssetStatus::IN_STOCK, $asset->status);
        $this->assertNull($asset->assigned_official_id);

        $closedAssignment = AssetAssignment::where('asset_id', $asset->id)
            ->where('official_id', $official->id)
            ->whereNotNull('returned_at')
            ->first();
        $this->assertNotNull($closedAssignment);
        $this->assertEquals('Good / Minor cosmetic wear', $closedAssignment->condition_in);
        $this->assertEquals($inventoryManager->id, $closedAssignment->return_recorded_by);
        $this->assertStringContainsString('Cluster decommissioned', $closedAssignment->remarks);

        // 3d. Decommission Asset
        $decomResponse = $this->actingAs($inventoryManager)->patch(route('assets.decommission', $asset), [
            'reason' => 'Cycle replacement and end of service life',
        ]);

        $decomResponse->assertRedirect();
        $asset->refresh();
        $this->assertSame(AssetStatus::DECOMMISSIONED, $asset->status);
        $this->assertNull($asset->assigned_official_id);

        // -------------------------------------------------------------------------
        // 4. Consumables & Atomic Stock Ledger Lifecycle
        // -------------------------------------------------------------------------
        // 4a. Create Consumable
        $createConsumableResponse = $this->actingAs($inventoryManager)->post(route('consumables.store'), [
            'name' => 'Cat6 Shielded Patch Cable 5m',
            'sku' => 'CON-CAT6-5M-E2E',
            'unit' => 'piece',
            'in_stock' => 0,
            'min_quantity' => 10,
            'max_quantity' => 200,
        ]);

        $createConsumableResponse->assertRedirect();
        /** @var Consumable $consumable */
        $consumable = Consumable::where('sku', 'CON-CAT6-5M-E2E')->firstOrFail();
        $this->assertSame(0, $consumable->in_stock);

        // 4b. Stock Operator posts Purchase Entry
        $purchaseResponse = $this->actingAs($stockOperator)->post(route('consumables.entries.store', $consumable), [
            'type' => StockEntryType::PURCHASE->value,
            'quantity' => 50,
            'remarks' => 'Bulk procurement batch PO-2026-901',
            'idempotency_key' => 'PO-2026-901-PURCHASE',
        ]);

        $purchaseResponse->assertRedirect();
        $consumable->refresh();
        $this->assertSame(50, $consumable->in_stock);

        $this->assertDatabaseHas('entries', [
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::PURCHASE->value,
            'quantity' => 50,
            'stock_after' => 50,
            'recorded_by' => $stockOperator->id,
            'idempotency_key' => 'PO-2026-901-PURCHASE',
        ]);

        // 4c. Stock Operator issues stock to Official
        $issueResponse = $this->actingAs($stockOperator)->post(route('consumables.entries.store', $consumable), [
            'type' => StockEntryType::ISSUE->value,
            'quantity' => 8,
            'recipient_official_id' => $official->id,
            'remarks' => 'Issued for Data Center rack cabling',
            'idempotency_key' => 'ISSUE-DC-RACK-001',
        ]);

        $issueResponse->assertRedirect();
        $consumable->refresh();
        $this->assertSame(42, $consumable->in_stock);

        $this->assertDatabaseHas('entries', [
            'consumable_id' => $consumable->id,
            'type' => StockEntryType::ISSUE->value,
            'quantity' => 8,
            'stock_after' => 42,
            'recipient_official_id' => $official->id,
            'recorded_by' => $stockOperator->id,
        ]);

        // -------------------------------------------------------------------------
        // 5. Vendor Agreement & Payment Schedule Lifecycle
        // -------------------------------------------------------------------------
        // 5a. Finance Operator creates Agreement
        $agreementResponse = $this->actingAs($financeOperator)->post(route('agreements.store'), [
            'name' => 'Data Center Fiber Connectivity AMC 2026',
            'agency' => 'Metro Telecom Ltd',
            'type' => 'Telecom Infrastructure',
            'annual_cost' => 120000.00,
            'currency' => 'INR',
            'billing_interval_months' => 3, // Quarterly: 4 milestones of 30,000 INR
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
            'remarks' => 'Quarterly advance payment schedule',
        ]);

        $agreementResponse->assertRedirect();
        /** @var Agreement $agreement */
        $agreement = Agreement::where('name', 'Data Center Fiber Connectivity AMC 2026')->firstOrFail();
        $this->assertEquals(120000.00, $agreement->annual_cost);
        $this->assertSame(3, $agreement->billing_interval_months);
        $this->assertNull($agreement->paid_till);

        // Verify that 4 payment milestones were generated
        $this->assertCount(4, $agreement->payments);
        $payments = $agreement->payments()->orderBy('due_date')->get();

        $this->assertEquals('2026-01-01', $payments[0]->due_date->format('Y-m-d'));
        $this->assertEquals(30000.00, $payments[0]->amount);
        $this->assertSame(PaymentStatus::PENDING, $payments[0]->status);

        $this->assertEquals('2026-04-01', $payments[1]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-07-01', $payments[2]->due_date->format('Y-m-d'));
        $this->assertEquals('2026-10-01', $payments[3]->due_date->format('Y-m-d'));

        // 5b. Finance Operator completes Q1 Payment
        $completeQ1Response = $this->actingAs($financeOperator)->post(route('payments.complete', $payments[0]), [
            'invoice_number' => 'INV-METRO-2026-Q1',
            'paid_date' => '2026-01-05',
        ]);

        $completeQ1Response->assertRedirect();
        $payments[0]->refresh();
        $agreement->refresh();

        $this->assertSame(PaymentStatus::COMPLETED, $payments[0]->status);
        $this->assertEquals('INV-METRO-2026-Q1', $payments[0]->invoice_number);
        $this->assertEquals($financeOperator->id, $payments[0]->completed_by);
        $this->assertEquals('2026-01-01', $agreement->paid_till->format('Y-m-d'));

        // 5c. Finance Operator completes Q2 Payment
        $completeQ2Response = $this->actingAs($financeOperator)->post(route('payments.complete', $payments[1]), [
            'invoice_number' => 'INV-METRO-2026-Q2',
            'paid_date' => '2026-04-02',
        ]);

        $completeQ2Response->assertRedirect();
        $payments[1]->refresh();
        $agreement->refresh();

        $this->assertSame(PaymentStatus::COMPLETED, $payments[1]->status);
        $this->assertEquals('INV-METRO-2026-Q2', $payments[1]->invoice_number);
        $this->assertEquals('2026-04-01', $agreement->paid_till->format('Y-m-d'));

        // -------------------------------------------------------------------------
        // 6. Tabular Exports & Audit Log Verification
        // -------------------------------------------------------------------------
        // 6a. Verify Assets Export
        $assetsExport = new AssetsExport();
        $headings = $assetsExport->headings();
        $this->assertContains('Asset Tag', $headings);
        $this->assertContains('Serial Number', $headings);
        $this->assertContains('Manufacturer', $headings);
        $this->assertContains('Location', $headings);

        $mappedAsset = $assetsExport->map($asset);
        $this->assertSame('TAG-SRV-E2E-01', $mappedAsset[0]);
        $this->assertSame('PowerEdge R750 Rack Server', $mappedAsset[1]);
        $this->assertSame('Server', $mappedAsset[2]);
        $this->assertSame('Decommissioned', $mappedAsset[3]);

        // 6b. Verify Agreements Export
        $agreementsExport = new AgreementsExport();
        $agreementHeadings = $agreementsExport->headings();
        $this->assertContains('Agreement Name', $agreementHeadings);
        $this->assertContains('Annual Cost', $agreementHeadings);
        $this->assertContains('Paid Till Date', $agreementHeadings);

        $mappedAgreement = $agreementsExport->map($agreement);
        $this->assertSame('Data Center Fiber Connectivity AMC 2026', $mappedAgreement[0]);
        $this->assertSame('Metro Telecom Ltd', $mappedAgreement[1]);
        $this->assertEquals(120000.00, (float) $mappedAgreement[3]);
        $this->assertSame('2026-04-01', $mappedAgreement[7]);

        // 6c. Verify HTTP Export Endpoints
        $assetExportHttp = $this->actingAs($inventoryManager)->get(route('assets.export'));
        $assetExportHttp->assertStatus(200);

        $agreementExportHttp = $this->actingAs($financeOperator)->get(route('agreements.export'));
        $agreementExportHttp->assertStatus(200);

        // 6d. Verify Activity Logs recorded
        $this->assertGreaterThan(0, Activity::count());
    }

    /**
     * Test role-based authorization matrix ensuring fine-grained access control across all domains.
     */
    public function test_role_based_access_control_matrix_across_all_modules(): void
    {
        $viewer = User::factory()->viewer()->create();
        $auditor = User::factory()->auditor()->create();
        $stockOp = User::factory()->stockOperator()->create();
        $finOp = User::factory()->financeOperator()->create();
        $manager = User::factory()->inventoryManager()->create();

        $asset = Asset::factory()->laptop()->inStock()->create();
        $consumable = Consumable::factory()->inStock(20)->create();
        $agreement = Agreement::factory()->create();
        $payment = Payment::factory()->create(['agreement_id' => $agreement->id]);
        $official = Official::factory()->create();

        // Viewer cannot mutate assets, consumables, agreements, or payments
        $this->actingAs($viewer)->post(route('assets.store'), ['name' => 'Unauthorized'])->assertStatus(403);
        $this->actingAs($viewer)->post(route('assets.assign', $asset), ['official_id' => $official->id])->assertStatus(403);
        $this->actingAs($viewer)->post(route('consumables.store'), ['name' => 'Unauthorized'])->assertStatus(403);
        $this->actingAs($viewer)->post(route('consumables.entries.store', $consumable), ['type' => 'purchase', 'quantity' => 5])->assertStatus(403);
        $this->actingAs($viewer)->post(route('agreements.store'), ['name' => 'Unauthorized'])->assertStatus(403);
        $this->actingAs($viewer)->post(route('payments.complete', $payment), ['invoice_number' => 'INV-TEST'])->assertStatus(403);

        // Stock Operator cannot manage agreements or complete payments
        $this->actingAs($stockOp)->post(route('agreements.store'), ['name' => 'Unauthorized'])->assertStatus(403);
        $this->actingAs($stockOp)->post(route('payments.complete', $payment), ['invoice_number' => 'INV-TEST'])->assertStatus(403);

        // Finance Operator cannot post stock entries or decommission assets
        $this->actingAs($finOp)->post(route('consumables.entries.store', $consumable), ['type' => 'purchase', 'quantity' => 5])->assertStatus(403);
        $this->actingAs($finOp)->patch(route('assets.decommission', $asset), ['reason' => 'Unauthorized'])->assertStatus(403);

        // Inventory Manager can manage assets and consumables, but cannot complete payments
        $this->actingAs($manager)->post(route('payments.complete', $payment), ['invoice_number' => 'INV-TEST'])->assertStatus(403);
    }

    /**
     * Test asset assignment chain: auto-closing active assignment when re-assigned directly to another official.
     */
    public function test_asset_assignment_chain_auto_closes_previous_assignment(): void
    {
        $manager = User::factory()->inventoryManager()->create();
        $official1 = Official::factory()->create(['name' => 'Alice Developer']);
        $official2 = Official::factory()->create(['name' => 'Bob Support']);

        $asset = Asset::factory()->laptop()->inStock()->create([
            'name' => 'MacBook Pro M3 Max',
            'serial_number' => 'MBP-M3-CHAIN-01',
        ]);

        $assignAction = app(AssignAssetAction::class);

        // First assignment
        $assign1 = $assignAction->execute(
            asset: $asset,
            official: $official1,
            actor: $manager,
            remarks: 'Initial assignment to Alice',
            conditionOut: 'Excellent'
        );

        $this->assertEquals($official1->id, $asset->fresh()->assigned_official_id);
        $this->assertSame(AssetStatus::IN_USE, $asset->fresh()->status);
        $this->assertNull($assign1->fresh()->returned_at);

        // Direct re-assignment to Bob without explicit return step
        $assign2 = $assignAction->execute(
            asset: $asset,
            official: $official2,
            actor: $manager,
            remarks: 'Transferred to Bob for urgent project',
            conditionOut: 'Good'
        );

        $this->assertEquals($official2->id, $asset->fresh()->assigned_official_id);
        $this->assertNotNull($assign1->fresh()->returned_at, 'Previous assignment must be auto-closed upon reassignment.');
        $this->assertNull($assign2->fresh()->returned_at, 'New assignment must remain open.');
        $this->assertStringContainsString('Auto-closed on re-assignment', $assign1->fresh()->remarks);

        // Return asset
        $returnAction = app(ReturnAssetAction::class);
        $returnedAssignment = $returnAction->execute(
            asset: $asset,
            actor: $manager,
            remarks: 'Returned from Bob',
            conditionIn: 'Good'
        );

        $this->assertNull($asset->fresh()->assigned_official_id);
        $this->assertSame(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertNotNull($returnedAssignment->fresh()->returned_at);
    }

    /**
     * Test stock ledger atomicity, idempotency, and insufficient stock safeguards.
     */
    public function test_consumable_stock_ledger_atomicity_and_idempotency(): void
    {
        $stockOp = User::factory()->stockOperator()->create();
        $official = Official::factory()->create();
        $consumable = Consumable::factory()->inStock(10)->create();

        $postStockAction = app(PostStockEntryAction::class);

        // 1. Idempotent purchase posting
        $entry1 = $postStockAction->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 15,
            recipient: null,
            user: $stockOp,
            remarks: 'Initial batch purchase',
            idempotencyKey: 'BATCH-PO-UNIQUE-101'
        );

        $this->assertSame(25, $consumable->fresh()->in_stock);
        $this->assertSame(25, $entry1->stock_after);

        // Repeat with identical idempotency key -> returns identical entry without mutating stock
        $entry2 = $postStockAction->execute(
            consumable: $consumable,
            type: StockEntryType::PURCHASE,
            quantity: 15,
            recipient: null,
            user: $stockOp,
            remarks: 'Initial batch purchase duplicate retry',
            idempotencyKey: 'BATCH-PO-UNIQUE-101'
        );

        $this->assertSame($entry1->id, $entry2->id);
        $this->assertSame(25, $consumable->fresh()->in_stock, 'Stock must not increase on duplicate idempotency key.');

        // 2. Insufficient stock check
        $this->expectException(InsufficientStockException::class);
        $postStockAction->execute(
            consumable: $consumable,
            type: StockEntryType::ISSUE,
            quantity: 100, // Exceeds available stock of 25
            recipient: $official,
            user: $stockOp,
            remarks: 'Over-issuance test'
        );
    }

    /**
     * Test payment schedule generation idempotency and progressive paid_till advancement.
     */
    public function test_agreement_schedule_idempotency_and_payment_progression(): void
    {
        $finOp = User::factory()->financeOperator()->create();

        $agreement = Agreement::factory()->create([
            'annual_cost' => 60000.00,
            'billing_interval_months' => 6, // Semi-annual: 2 payments of 30,000
            'billing_anchor_date' => '2026-01-01',
            'expiry' => '2026-12-31',
            'paid_till' => null,
        ]);

        $generateAction = app(GeneratePaymentScheduleAction::class);
        $completeAction = app(CompletePaymentAction::class);

        // 1. Generate schedule
        $payments1 = $generateAction->execute($agreement);
        $this->assertCount(2, $payments1);
        $this->assertCount(2, $agreement->payments()->get());

        // 2. Call generate schedule again -> must be idempotent and not create duplicate records
        $payments2 = $generateAction->execute($agreement);
        $this->assertCount(2, $payments2);
        $this->assertCount(2, $agreement->payments()->get());

        // 3. Complete first milestone
        $payment1 = $agreement->payments()->where('due_date', '2026-01-01')->firstOrFail();
        $completeAction->execute(
            payment: $payment1,
            user: $finOp,
            invoiceNumber: 'INV-SEMI-01',
            paidDate: Carbon::parse('2026-01-10')
        );

        $agreement->refresh();
        $this->assertEquals('2026-01-01', $agreement->paid_till->format('Y-m-d'));

        // 4. Complete second milestone
        $payment2 = $agreement->payments()->where('due_date', '2026-07-01')->firstOrFail();
        $completeAction->execute(
            payment: $payment2,
            user: $finOp,
            invoiceNumber: 'INV-SEMI-02',
            paidDate: Carbon::parse('2026-07-10')
        );

        $agreement->refresh();
        $this->assertEquals('2026-07-01', $agreement->paid_till->format('Y-m-d'));
    }
}
