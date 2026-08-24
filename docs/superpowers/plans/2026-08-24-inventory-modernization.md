# Inventory Modernization & Asset Consolidation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a clean, secure, and production-ready IT Inventory and Asset Management application on a supported Laravel/PHP baseline with a unified asset model, immutable stock ledger, idempotent payment schedules, role-based authorization, and a verifiable read-only legacy data importer.

**Architecture:** Domain-driven service layer separating business rules from HTTP controllers; immutable ledgers with pessimistic database locking (`lockForUpdate`) for inventory; deterministic schedule generation for vendor agreements; application-owned interface boundaries for audit logging, exports, and private attachments; and a dual-connection read-only legacy importer storing unmapped source data in `legacy_payload`.

**Tech Stack:** The replaceable platform baseline currently selects PHP 8.4+, Laravel 13.x, MySQL 8.0+, Redis (for cache/queues/locks), the official Laravel Livewire starter kit, Tailwind CSS 4+, Vite 8+, Spatie Activitylog v5+, Maatwebsite Excel v4+, and PHPUnit 12+.

**Spec:** [`docs/superpowers/specs/2026-08-24-inventory-modernization-design.md`](file:///C:/Users/IT/Documents/GitHub/inventory-management-software-laravel/docs/superpowers/specs/2026-08-24-inventory-modernization-design.md)

## Global Constraints

- Never drop, alter, or write to the source legacy database during migration.
- All mutating operations must be protected by CSRF, Form Request validation, and explicit server-side policy authorization checks.
- Stock quantities and ledger entries are strictly non-negative; corrections must use compensating entries, never direct updates/deletes.
- Payment schedule generation must be deterministic and idempotent using derived `schedule_key` values.
- Raw legacy row data must be preserved in `legacy_payload` (JSON) during data migration to guarantee zero data loss.
- Sensitive personnel data (developer salaries, personal contact information) must be encrypted at rest and restricted by permission.
- No public user registration; account creation is restricted to administrators and expiring invitations.

---

## File Structure & Decomposition Plan

```
app/
├── Contracts/
│   ├── AuditRecorder.php
│   ├── TabularExporter.php
│   └── AttachmentStore.php
├── Enums/
│   ├── AssetType.php
│   ├── AssetStatus.php
│   ├── StockEntryType.php
│   ├── PaymentStatus.php
│   ├── AgreementFrequency.php
│   └── UserRole.php
├── Models/
│   ├── User.php
│   ├── Asset.php
│   ├── AssetAssignment.php
│   ├── Consumable.php
│   ├── Entry.php
│   ├── Agreement.php
│   ├── Payment.php
│   ├── Official.php
│   ├── Developer.php
│   ├── Devcat.php
│   ├── Task.php
│   ├── FileRecord.php
│   ├── Attachment.php
│   └── LegacyImportRun.php
├── Services/
│   ├── Assets/
│   │   ├── CreateAssetAction.php
│   │   ├── UpdateAssetAction.php
│   │   ├── AssignAssetAction.php
│   │   ├── ReturnAssetAction.php
│   │   └── DecommissionAssetAction.php
│   ├── Inventory/
│   │   └── PostStockEntryAction.php
│   ├── Agreements/
│   │   ├── GeneratePaymentScheduleAction.php
│   │   └── CompletePaymentAction.php
│   └── Importer/
│       ├── LegacyImportManager.php
│       ├── TableImporters/
│       └── ReconciliationReporter.php
├── Policies/
│   ├── AssetPolicy.php
│   ├── ConsumablePolicy.php
│   ├── AgreementPolicy.php
│   ├── PaymentPolicy.php
│   ├── OfficialPolicy.php
│   ├── DeveloperPolicy.php
│   └── TaskPolicy.php
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── AssetController.php
│   │   ├── AssetAssignmentController.php
│   │   ├── ConsumableController.php
│   │   ├── EntryController.php
│   │   ├── AgreementController.php
│   │   ├── PaymentController.php
│   │   ├── OfficialController.php
│   │   ├── DeveloperController.php
│   │   ├── TaskController.php
│   │   ├── FileRecordController.php
│   │   ├── LocationController.php
│   │   └── ManufacturerController.php
│   └── Requests/
└── Console/
    └── Commands/
        ├── ImportLegacyInventoryCommand.php
        └── GenerateScheduledPaymentsCommand.php
```

---

## Tasks

### Task 1: Environment, Dependencies & Base Scaffolding

**Files:**
- Modify: `composer.json`
- Modify: `package.json`
- Create: `config/database.php`
- Create: `bootstrap/app.php`
- Create: `app/Enums/UserRole.php`
- Test: `tests/Unit/PlatformBaselineTest.php`

**Interfaces:**
- Produces: Clean Laravel 13 bootstrap configuration, database configuration supporting both `mysql` and read-only `legacy` connections, and runtime baseline test.

- [ ] **Step 1: Write baseline requirement test**

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class PlatformBaselineTest extends TestCase
{
    public function test_runtime_and_connections_configured(): void
    {
        $this->assertGreaterThanOrEqual(80400, PHP_VERSION_ID, 'PHP version must be >= 8.4');
        $this->assertArrayHasKey('legacy', config('database.connections'), 'Legacy connection must be defined');
        $this->assertTrue(config('database.connections.legacy.read_only') ?? true);
    }
}
```

- [ ] **Step 2: Update `composer.json` and `package.json`**
Remove legacy packages (`fideloper/proxy`, `fruitcake/laravel-cors`, `facade/ignition`, `laravel/ui`, `laravel-mix`). Apply the selected platform baseline: PHP 8.4+, Laravel 13, the official Livewire starter-kit dependency set, Spatie Activitylog v5+, Maatwebsite Excel v4+, Tailwind CSS 4+, and Vite 8+.

- [ ] **Step 3: Configure `bootstrap/app.php` and dual database connections**
Configure `config/database.php` with a standard `mysql` connection and a read-only `legacy` connection.

- [ ] **Step 4: Run test to verify configuration passes**
Run: `php artisan test --filter=PlatformBaselineTest`
Expected: PASS

---

### Task 2: Authentication, Roles & Authorization Policies

**Files:**
- Create: `app/Enums/UserRole.php`
- Modify: `app/Models/User.php`
- Create: `database/migrations/2026_08_24_000001_create_users_and_roles_tables.php`
- Create: `app/Policies/AssetPolicy.php`
- Create: `app/Policies/ConsumablePolicy.php`
- Create: `app/Policies/AgreementPolicy.php`
- Test: `tests/Feature/AuthorizationPolicyTest.php`

**Interfaces:**
- Produces: `UserRole` enum (`ADMIN`, `INVENTORY_MANAGER`, `STOCK_OPERATOR`, `FINANCE_OPERATOR`, `VIEWER`, `AUDITOR`), role check methods on `User` model, and server-side policy guards.

- [ ] **Step 1: Write policy authorization tests**

```php
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_create_assets(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        $this->actingAs($viewer)->get(route('assets.create'))->assertStatus(403);
    }

    public function test_inventory_manager_can_create_assets(): void
    {
        $manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $this->actingAs($manager)->get(route('assets.create'))->assertStatus(200);
    }
}
```

- [ ] **Step 2: Implement User model roles and Policies**
Implement `UserRole` enum with backed values, migrate users table with `role` column, and implement `AssetPolicy`, `ConsumablePolicy`, `AgreementPolicy`, `PaymentPolicy` enforcing the 6-role permission matrix from Section 7.1 of the design spec.

- [ ] **Step 3: Run policy tests**
Run: `php artisan test --filter=AuthorizationPolicyTest`
Expected: PASS

---

### Task 3: Master Data, Locations, Manufacturers & File Attachments

**Files:**
- Create: `database/migrations/2026_08_24_000002_create_master_data_and_attachments_tables.php`
- Create: `app/Models/Location.php`
- Create: `app/Models/Manufacturer.php`
- Create: `app/Models/FileRecord.php`
- Create: `app/Models/Attachment.php`
- Create: `app/Contracts/AttachmentStore.php`
- Create: `app/Services/Storage/PrivateDiskAttachmentStore.php`
- Test: `tests/Unit/AttachmentStoreTest.php`

**Interfaces:**
- Produces: `AttachmentStore` contract with `store(UploadedFile $file, Model $attachable, User $user): Attachment` and `retrieve(Attachment $attachment): StreamedResponse`.

- [ ] **Step 1: Write failing attachment store test**

```php
<?php

namespace Tests\Unit;

use App\Contracts\AttachmentStore;
use App\Models\Attachment;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_securely_store_and_checksum_private_file(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $record = FileRecord::create(['name' => 'Invoice 2026', 'subject' => 'Server Purchase']);
        $file = UploadedFile::fake()->create('invoice.pdf', 500, 'application/pdf');

        $store = app(AttachmentStore::class);
        $attachment = $store->store($file, $record, $user);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'attachable_id' => $record->id,
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf'
        ]);
        Storage::disk('private')->assertExists($attachment->path);
    }
}
```

- [ ] **Step 2: Create migrations and models for master data and attachments**
Implement `Location`, `Manufacturer`, `FileRecord`, `Attachment`, and `PrivateDiskAttachmentStore` computing SHA-256 checksums on private disk.

- [ ] **Step 3: Run attachment store test**
Run: `php artisan test --filter=AttachmentStoreTest`
Expected: PASS

---

### Task 4: Unified Asset Domain & Assignment History

**Files:**
- Create: `database/migrations/2026_08_24_000003_create_assets_and_assignments_tables.php`
- Create: `app/Enums/AssetType.php`
- Create: `app/Enums/AssetStatus.php`
- Create: `app/Models/Asset.php`
- Create: `app/Models/AssetAssignment.php`
- Create: `app/Services/Assets/CreateAssetAction.php`
- Create: `app/Services/Assets/AssignAssetAction.php`
- Create: `app/Services/Assets/ReturnAssetAction.php`
- Create: `app/Services/Assets/DecommissionAssetAction.php`
- Test: `tests/Feature/AssetDomainTest.php`

**Interfaces:**
- Produces: `Asset` model with casts (`AssetType`, `AssetStatus`, `specifications` $\rightarrow$ JSON, `legacy_payload` $\rightarrow$ JSON), and service actions for checkout, return, and decommissioning.

- [ ] **Step 1: Write asset domain & assignment lifecycle test**

```php
<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Official;
use App\Models\User;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\ReturnAssetAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_assignment_creates_history_and_locks_row(): void
    {
        $user = User::factory()->create();
        $official = Official::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $asset = Asset::create([
            'name' => 'Dell Latitude 5420',
            'asset_type' => AssetType::LAPTOP,
            'status' => AssetStatus::IN_STOCK,
            'serial_number' => 'SN12345678'
        ]);

        $assignAction = app(AssignAssetAction::class);
        $assignAction->execute($asset, $official, $user, 'Initial issue');

        $this->assertEquals(AssetStatus::IN_USE, $asset->fresh()->status);
        $this->assertEquals($official->id, $asset->fresh()->assigned_official_id);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'returned_at' => null
        ]);

        $returnAction = app(ReturnAssetAction::class);
        $returnAction->execute($asset, $user, 'Returned in good condition');

        $this->assertEquals(AssetStatus::IN_STOCK, $asset->fresh()->status);
        $this->assertNull($asset->fresh()->assigned_official_id);
    }
}
```

- [ ] **Step 2: Implement migration, models and asset action services**
Write migration for `assets` and `asset_assignments` as detailed in Section 4.1 & 4.2 of the spec. Implement `AssignAssetAction`, `ReturnAssetAction`, and `DecommissionAssetAction` running within DB transactions with row-level locks.

- [ ] **Step 3: Run asset domain test**
Run: `php artisan test --filter=AssetDomainTest`
Expected: PASS

---

### Task 5: Immutable Consumables Ledger & Concurrency Protections

**Files:**
- Create: `database/migrations/2026_08_24_000004_create_consumables_and_entries_tables.php`
- Create: `app/Enums/StockEntryType.php`
- Create: `app/Models/Consumable.php`
- Create: `app/Models/Entry.php`
- Create: `app/Services/Inventory/PostStockEntryAction.php`
- Create: `app/Exceptions/InsufficientStockException.php`
- Test: `tests/Feature/StockLedgerConcurrencyTest.php`

**Interfaces:**
- Produces: `PostStockEntryAction::execute(Consumable $consumable, StockEntryType $type, int $quantity, ?Official $recipient, User $user, ?string $remarks, ?string $idempotencyKey): Entry`

- [ ] **Step 1: Write stock ledger & concurrency test**

```php
<?php

namespace Tests\Feature;

use App\Enums\StockEntryType;
use App\Exceptions\InsufficientStockException;
use App\Models\Consumable;
use App\Models\User;
use App\Services\Inventory\PostStockEntryAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_cannot_become_negative_and_entries_are_atomic(): void
    {
        $user = User::factory()->create();
        $consumable = Consumable::create(['name' => 'CAT6 Cable', 'in_stock' => 5, 'min_quantity' => 2]);
        $action = app(PostStockEntryAction::class);

        $entry = $action->execute($consumable, StockEntryType::PURCHASE, 10, null, $user, 'PO-101', 'PO101-KEY');
        $this->assertEquals(15, $consumable->fresh()->in_stock);
        $this->assertEquals(15, $entry->stock_after);

        $this->expectException(InsufficientStockException::class);
        $action->execute($consumable, StockEntryType::ISSUE, 20, null, $user, 'Excess issue', 'REQ-01');
    }

    public function test_idempotent_key_prevents_duplicate_stock_mutations(): void
    {
        $user = User::factory()->create();
        $consumable = Consumable::create(['name' => 'Mouse', 'in_stock' => 10]);
        $action = app(PostStockEntryAction::class);

        $entry1 = $action->execute($consumable, StockEntryType::ISSUE, 2, null, $user, 'Req', 'KEY-999');
        $entry2 = $action->execute($consumable, StockEntryType::ISSUE, 2, null, $user, 'Req', 'KEY-999');

        $this->assertEquals($entry1->id, $entry2->id);
        $this->assertEquals(8, $consumable->fresh()->in_stock);
    }
}
```

- [ ] **Step 2: Implement PostStockEntryAction with `lockForUpdate()`**
Execute transaction, lock consumable row, check balance for `ISSUE`/`ADJUSTMENT_OUT`, calculate `stock_after`, record `Entry`, and update `in_stock`.

- [ ] **Step 3: Run stock ledger tests**
Run: `php artisan test --filter=StockLedgerConcurrencyTest`
Expected: PASS

---

### Task 6: Agreements & Idempotent Payment Schedules

**Files:**
- Create: `database/migrations/2026_08_24_000005_create_agreements_and_payments_tables.php`
- Create: `app/Enums/PaymentStatus.php`
- Create: `app/Models/Agreement.php`
- Create: `app/Models/Payment.php`
- Create: `app/Services/Agreements/GeneratePaymentScheduleAction.php`
- Create: `app/Services/Agreements/CompletePaymentAction.php`
- Test: `tests/Feature/AgreementPaymentScheduleTest.php`

**Interfaces:**
- Produces: `GeneratePaymentScheduleAction::execute(Agreement $agreement): Collection` and `CompletePaymentAction::execute(Payment $payment, User $user, string $invoiceNumber, Carbon $paidDate): Payment`.

- [ ] **Step 1: Write agreement schedule generation test**

```php
<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Agreement;
use App\Models\User;
use App\Services\Agreements\GeneratePaymentScheduleAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementPaymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_schedule_is_deterministic_and_idempotent(): void
    {
        $agreement = Agreement::create([
            'name' => 'Cisco Switch AMC',
            'agency' => 'Cisco Systems',
            'annual_cost' => 120000,
            'billing_interval_months' => 3, // Quarterly
            'billing_anchor_date' => Carbon::parse('2026-01-01'),
            'expiry' => Carbon::parse('2026-12-31')
        ]);

        $action = app(GeneratePaymentScheduleAction::class);
        $payments = $action->execute($agreement);

        $this->assertCount(4, $payments);
        $this->assertEquals(30000, $payments->first()->amount);

        // Re-running action should not duplicate payments
        $rerunPayments = $action->execute($agreement);
        $this->assertCount(4, $rerunPayments);
    }
}
```

- [ ] **Step 2: Implement Agreement, Payment models and Schedule Generator**
Calculate quarterly/monthly intervals with end-of-month anchoring, generating unique deterministic `schedule_key = sha1("{$agreement->id}-{$dueDate}")`.

- [ ] **Step 3: Run agreement payment tests**
Run: `php artisan test --filter=AgreementPaymentScheduleTest`
Expected: PASS

---

### Task 7: Officials, Developers & Encrypted Personal Fields

**Files:**
- Create: `database/migrations/2026_08_24_000006_create_personnel_and_tasks_tables.php`
- Create: `app/Models/Official.php`
- Create: `app/Models/Developer.php`
- Create: `app/Models/Devcat.php`
- Create: `app/Models/Task.php`
- Test: `tests/Unit/PersonnelEncryptionTest.php`

**Interfaces:**
- Produces: `Developer` model with encrypted attributes (`salary`, `contact_number`), `Devcat` categories, and `Task` model.

- [ ] **Step 1: Write personnel encryption & model test**

```php
<?php

namespace Tests\Unit;

use App\Models\Devcat;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersonnelEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_developer_fields_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $devcat = Devcat::create(['name' => 'Fullstack Dev']);

        $dev = Developer::create([
            'name' => 'Alice Smith',
            'reporting_id' => $user->id,
            'category_id' => $devcat->id,
            'salary' => '95000',
            'status' => 'active'
        ]);

        $rawRow = DB::table('developers')->where('id', $dev->id)->first();
        $this->assertNotEquals('95000', $rawRow->salary, 'Salary must not be plain text in database');
        $this->assertEquals('95000', $dev->fresh()->salary, 'Model should decrypt salary on read');
    }
}
```

- [ ] **Step 2: Implement migrations and encrypted models**
Use Laravel's `encrypted` attribute casts on `Developer::class` for sensitive fields.

- [ ] **Step 3: Run encryption test**
Run: `php artisan test --filter=PersonnelEncryptionTest`
Expected: PASS

---

### Task 8: Audit Logging & Tabular Export Adapters

**Files:**
- Create: `app/Contracts/AuditRecorder.php`
- Create: `app/Contracts/TabularExporter.php`
- Create: `app/Services/Audit/SpatieAuditRecorder.php`
- Create: `app/Services/Export/MaatwebsiteTabularExporter.php`
- Create: `app/Exports/AssetsExport.php`
- Create: `app/Exports/AgreementsExport.php`
- Test: `tests/Unit/AuditAndExportTest.php`

**Interfaces:**
- Produces: `AuditRecorder::record(string $event, Model $subject, ?array $properties = []): void` and `TabularExporter::export(ExportableQuery $query, string $filename): BinaryFileResponse`.

- [ ] **Step 1: Write audit & export test**

```php
<?php

namespace Tests\Unit;

use App\Contracts\AuditRecorder;
use App\Contracts\TabularExporter;
use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_recorder_captures_actor_and_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $asset = Asset::create(['name' => 'Server 1', 'asset_type' => 'server']);

        $recorder = app(AuditRecorder::class);
        $recorder->record('asset.created', $asset, ['name' => 'Server 1']);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $asset->id,
            'causer_id' => $user->id,
            'description' => 'asset.created'
        ]);
    }
}
```

- [ ] **Step 2: Implement Spatie v4+ Audit Recorder & Maatwebsite Excel Exporter**
Implement `SpatieAuditRecorder` wrapping ActivityLog v4 `getActivitylogOptions()` and `MaatwebsiteTabularExporter`.

- [ ] **Step 3: Run audit & export test**
Run: `php artisan test --filter=AuditAndExportTest`
Expected: PASS

---

### Task 9: Read-Only Legacy Data Importer & Reconciler

**Files:**
- Create: `app/Services/Importer/LegacyImportManager.php`
- Create: `app/Services/Importer/TableImporters/AssetTableImporter.php`
- Create: `app/Services/Importer/TableImporters/ConsumableTableImporter.php`
- Create: `app/Services/Importer/TableImporters/AgreementTableImporter.php`
- Create: `app/Services/Importer/ReconciliationReporter.php`
- Create: `app/Console/Commands/ImportLegacyInventoryCommand.php`
- Test: `tests/Feature/LegacyImporterVerificationTest.php`

**Interfaces:**
- Produces: CLI command `php artisan inventory:import-legacy {--dry-run} {--resume} {--verify}` with reconciliation metrics report.

- [ ] **Step 1: Write legacy importer fixture test**

```php
<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\LegacyImportRun;
use App\Services\Importer\LegacyImportManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyImporterVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_maps_legacy_tables_into_unified_assets_preserving_raw_payload(): void
    {
        // Seed simulated legacy tables on legacy connection
        DB::connection('legacy')->table('desktops')->insert([
            'id' => 101,
            'serial' => 'SN-DESK-01',
            'brand' => 'HP',
            'processor' => 'i7',
            'ram' => '16GB',
            'hdd' => '512GB SSD',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $manager = app(LegacyImportManager::class);
        $result = $manager->import(dryRun: false);

        $this->assertTrue($result->isSuccessful());
        $asset = Asset::where('legacy_source', 'desktop')->where('legacy_id', 101)->first();
        $this->assertNotNull($asset);
        $this->assertEquals('SN-DESK-01', $asset->serial_number);
        $this->assertEquals('i7', $asset->specifications['processor']);
        $this->assertArrayHasKey('hdd', $asset->legacy_payload);
    }
}
```

- [ ] **Step 2: Implement TableImporters with chunking, dry-run, and reconciliation**
Read from `legacy` connection in chunks of 500, insert into `assets` with `legacy_payload`, and generate discrepancy reports for missing foreign keys.

- [ ] **Step 3: Run importer test**
Run: `php artisan test --filter=LegacyImporterVerificationTest`
Expected: PASS

---

### Task 10: HTTP Layer, Controllers, Form Requests & Routes

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `app/Http/Controllers/AssetController.php`
- Create: `app/Http/Controllers/ConsumableController.php`
- Create: `app/Http/Controllers/EntryController.php`
- Create: `app/Http/Controllers/AgreementController.php`
- Create: `app/Http/Controllers/PaymentController.php`
- Create: `app/Http/Requests/StoreAssetRequest.php`
- Create: `app/Http/Requests/StoreEntryRequest.php`
- Create: `app/Http/Requests/StoreAgreementRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/HttpRoutesTest.php`

**Interfaces:**
- Produces: RESTful authenticated routes with Form Request validations and policy checks.

- [ ] **Step 1: Write HTTP route feature test**

```php
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_dashboard_and_asset_endpoints(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('assets.index', ['type' => 'laptop']))
            ->assertStatus(200);
    }
}
```

- [ ] **Step 2: Implement Controllers, Form Requests and register routes**
Implement `DashboardController` providing KPI counts (expiring agreements, low stock, due payments) and standard resource controllers.

- [ ] **Step 3: Run HTTP routes test**
Run: `php artisan test --filter=HttpRoutesTest`
Expected: PASS

---

### Task 11: Frontend UI/UX, Navigation & Dashboard Views

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/components/sidebar.blade.php`
- Create: `resources/views/components/topbar.blade.php`
- Create: `resources/views/dashboard.blade.php`
- Create: `resources/views/assets/index.blade.php`
- Create: `resources/views/assets/create.blade.php`
- Create: `resources/views/assets/show.blade.php`
- Create: `resources/views/consumables/index.blade.php`
- Create: `resources/views/agreements/index.blade.php`
- Create: `resources/views/payments/index.blade.php`
- Test: `tests/Feature/FrontendViewRenderTest.php`

**Interfaces:**
- Produces: Responsive Tailwind + Alpine layout with collapsible sidebar, KPI cards, filter chips, and accessible tables.

- [ ] **Step 1: Write Blade rendering test**

```php
<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendViewRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_kpis_and_recent_activity(): void
    {
        $user = User::factory()->create();
        Asset::create(['name' => 'ThinkPad T14', 'asset_type' => 'laptop']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('ThinkPad T14');
        $response->assertSee('IT Assets');
    }
}
```

- [ ] **Step 2: Create Blade layouts, components, and views**
Build clean Tailwind CSS views with Alpine.js instant search/filter, modal stock issuance, and accessible responsive navigation.

- [ ] **Step 3: Run view rendering test**
Run: `php artisan test --filter=FrontendViewRenderTest`
Expected: PASS

---

### Task 12: End-to-End Quality Gates, Verification & Documentation

**Files:**
- Create: `tests/Feature/EndToEndInventoryFlowTest.php`
- Create: `database/seeders/FictionalDatabaseSeeder.php`
- Modify: `README.md`
- Test: Full test suite execution

**Interfaces:**
- Produces: Complete end-to-end integration test verifying asset lifecycle, stock ledger, agreement payment generation, and anonymized development seed fixtures.

- [ ] **Step 1: Write end-to-end workflow test**

```php
<?php

namespace Tests\Feature;

use App\Enums\StockEntryType;
use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\Consumable;
use App\Models\Official;
use App\Models\User;
use App\Services\Agreements\GeneratePaymentScheduleAction;
use App\Services\Inventory\PostStockEntryAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndInventoryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_organization_inventory_flow(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $official = Official::create(['name' => 'Alice Wong', 'department' => 'Engineering']);
        $consumable = Consumable::create(['name' => 'Toner Cartridge', 'in_stock' => 10, 'min_quantity' => 3]);

        // 1. Stock issue
        app(PostStockEntryAction::class)->execute($consumable, StockEntryType::ISSUE, 2, $official, $admin, 'Department allocation');
        $this->assertEquals(8, $consumable->fresh()->in_stock);

        // 2. Agreement & Payment generation
        $agreement = Agreement::create([
            'name' => 'Firewall License',
            'annual_cost' => 60000,
            'billing_interval_months' => 6,
            'billing_anchor_date' => Carbon::now(),
            'expiry' => Carbon::now()->addYear()
        ]);
        $payments = app(GeneratePaymentScheduleAction::class)->execute($agreement);
        $this->assertCount(2, $payments);
    }
}
```

- [ ] **Step 2: Build `FictionalDatabaseSeeder` without real PII**
Replace old hardcoded staff details with synthetic Faker fixtures. Update `README.md` with modern setup instructions, testing commands, and legacy importer usage.

- [ ] **Step 3: Run complete verification suite**
Run: `php artisan test`
Expected: All tests PASS (0 failures, 0 errors).
