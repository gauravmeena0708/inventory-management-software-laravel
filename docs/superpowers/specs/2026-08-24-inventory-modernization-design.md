# System Design Specification: Inventory Modernization and Asset Consolidation

- **Date:** 2026-08-24
- **Status:** Version-independent architecture specification
- **Project:** IT Inventory and Asset Management System
- **Target platform:** A supported Laravel/PHP baseline selected through the platform policy in this document
- **Production database:** MySQL; the supported server version is selected in the platform baseline, and integration tests use the same engine
- **Version record:** See `2026-08-24-platform-baseline.md` for replaceable implementation versions.

---

## 1. Executive Summary

The existing repository is a partially implemented Laravel 8 application with broken fresh-install migrations, inconsistent forms and models, incomplete CRUD controllers, unsafe stock mutations, weak authorization, legacy dependencies, and personal or organizational data embedded in seeders.

This modernization will be implemented as a **fresh application on a supported Laravel baseline with a read-only legacy importer**, rather than an in-place framework upgrade. Business rules and data will be ported deliberately; legacy framework scaffolding, copied views, and empty controller actions will not be carried forward.

### Goals

1. Move to a supported Laravel and PHP platform without coupling the architecture to one framework major.
2. Consolidate desktop, laptop, server, device/switch, and storage records into one asset domain without losing legacy values.
3. Implement complete, authorized workflows for assets, consumables, agreements, payments, personnel, tasks, master data, documents, reports, and audit history.
4. Guarantee stock and payment consistency under retries and concurrent requests.
5. Provide a dry-runnable, resumable, verifiable, and reversible migration process.
6. Remove committed personal data and known credentials from normal development fixtures.
7. Establish automated quality, security, deployment, backup, and recovery controls.

### Non-goals for the first release

- A public API or mobile application.
- Procurement approval workflows beyond agreement and payment tracking.
- Depreciation/accounting calculations.
- Automatic discovery of devices on the network.
- Dropping or modifying the source legacy database during migration.

---

## 2. Architectural Decisions

### 2.1 Application strategy

Create a clean application from a supported Laravel skeleton and port behavior into it. The legacy application and its database remain available in read-only mode until the new system passes reconciliation and user acceptance.

Two database connections are used during transition:

- `legacy`: read-only connection to a restored production snapshot or the legacy database.
- `mysql`: the new application database.

The importer reads from `legacy` and writes only to `mysql`. It must never drop or alter legacy tables.

### 2.2 Platform capabilities

- A Laravel release that remains under security support for at least 12 months after the planned production launch.
- A PHP release supported by both that Laravel release and the PHP project for the same operating window.
- A maintained authentication foundation that provides login, invitation or administrator-controlled account creation, password reset, email verification, throttling, and optional 2FA.
- A server-rendered Blade-oriented UI. A maintained reactive layer may be used for interactive components, but domain behavior must not depend on it.
- The framework-supported frontend bundler and an accessible utility/component styling system selected in the platform baseline.
- MySQL as the canonical production database, using a server release supported through the planned operating window.
- Redis-compatible cache, session, queue, and lock infrastructure when multiple application instances are deployed.

SQLite may be used for isolated unit tests, but database behavior, concurrency, migrations, and importer tests must run against MySQL.

### 2.3 Platform selection policy

The architecture specifies capabilities, not package majors. The separate platform baseline records the versions selected for an implementation. Composer and frontend lock files remain the exact build source of truth.

Before a baseline is adopted or renewed:

1. Verify the framework and runtime security-support dates against the planned launch and maintenance period.
2. Verify every required package against that framework/runtime combination with dependency resolution and its maintained test matrix.
3. Prefer framework capabilities over third-party packages where the framework now supplies the requirement.
4. Reject abandoned packages or isolate them behind an application-owned interface with an agreed replacement path.
5. Run the full test, static-analysis, build, migration, and importer suite on the proposed baseline.
6. Record the decision and next mandatory review date in the platform baseline.

Legacy dependencies such as `laravel/ui`, `fideloper/proxy`, `fruitcake/laravel-cors`, `facade/ignition`, Laravel Mix, and Webpack are not ported merely because the old application used them. Each capability is reselected from the supported baseline.

### 2.4 Framework and package boundaries

Framework-version independence does not mean avoiding Laravel. It means preventing volatile scaffolding and package APIs from becoming business rules.

- Stock, payment-schedule, assignment, import, and reconciliation rules live in application-owned services with framework-agnostic inputs and outputs.
- Controllers, console commands, queued jobs, and reactive UI components call the same application services.
- Authentication starter-kit code is replaceable presentation/infrastructure; authorization decisions live in application policies and permission definitions.
- Activity logging is accessed through an application-owned `AuditRecorder` contract.
- Spreadsheet generation is accessed through an application-owned `TabularExporter` contract.
- Private attachments are accessed through an application-owned `AttachmentStore` contract.
- Legacy data access is isolated behind `LegacySource` readers and per-table mappers.
- Dates used by scheduling logic come from an injected clock rather than direct global time calls.
- Package-specific models, facades, events, and DTOs do not cross these boundaries.

Laravel migrations, Eloquent, validation, policies, queues, and storage remain valid infrastructure choices. The boundary is intended to make major upgrades localized, not to create a second general-purpose framework.

### 2.5 Upgrade and maintenance policy

- Apply compatible framework, PHP, package, Node, and frontend security/patch releases continuously after CI passes.
- Review dependency health and security advisories at least monthly.
- Review the platform baseline quarterly and before every production launch.
- Evaluate each new Laravel major within 90 days of release; upgrade when it improves the support runway and the application test suite is green.
- Begin a mandatory major upgrade before the deployed major has less than 12 months of security support remaining.
- Use automated dependency-update pull requests, but never auto-merge changes that fail application, migration, importer, or browser tests.
- A platform-major change normally updates the baseline and adapters, not this architecture specification. Change this document only when domain behavior or an architectural boundary changes.

---

## 3. Domain Architecture

```text
Browser
  |
  | HTTPS
  v
Supported Laravel application
  |- Authentication, verification, 2FA, throttling
  |- Policies and role-based permissions
  |- Server-rendered presentation and optional reactive components
  |- Form Requests and action/service classes
  |- Queued exports, notifications, and scheduled payment generation
  |
  +-- Asset domain
  +-- Consumable ledger domain
  +-- Agreement and payment domain
  +-- Personnel and task domain
  +-- File registry and attachment domain
  +-- Audit and reporting domain
  |
  +-- MySQL / Redis / private file storage
```

Controllers coordinate HTTP behavior only. Business mutations live in focused services/actions such as:

- `CreateAsset`, `UpdateAsset`, `AssignAsset`, and `DecommissionAsset`
- `PostStockEntry`
- `GeneratePaymentSchedule` and `CompletePayment`
- `ImportLegacyInventory` and per-table importer classes

All request data is validated through Form Requests. Every mutating action performs an explicit policy check.

---

## 4. Database Design

Database changes must be expressed with Laravel migrations rather than vendor-specific raw SQL.

### 4.1 Assets

The `assets` table contains common and operationally important fields. Rare legacy-only values remain available in `legacy_payload` so consolidation cannot silently discard them.

```text
assets
  id                         bigint primary key
  legacy_source              varchar(32) nullable
  legacy_id                  bigint nullable
  asset_tag                  varchar(100) nullable
  name                       varchar(255)
  asset_type                 varchar(32), indexed
  manufacturer_id            nullable foreign key, null on delete
  manufacturer_name_legacy   varchar(255) nullable
  location_id                nullable foreign key, null on delete
  location_text_legacy       varchar(255) nullable
  assigned_official_id       nullable foreign key, null on delete
  status                     varchar(32), indexed
  serial_number              varchar(191) nullable, indexed
  model_number               varchar(191) nullable
  part_code                  varchar(191) nullable
  ip_address                 varchar(45) nullable
  mac_address                varchar(45) nullable
  operating_system           varchar(100) nullable
  description                text nullable
  specifications             json nullable
  purchase_date              date nullable
  purchase_cost              decimal(15,2) nullable
  currency                   char(3), default INR
  warranty_expiry            date nullable
  end_of_sale                date nullable
  end_of_support             date nullable
  amc_start                  date nullable
  amc_end                    date nullable
  amc_cost                   decimal(15,2) nullable
  contract_type              varchar(100) nullable
  contract_reference         varchar(255) nullable
  file_id                    nullable foreign key, null on delete
  legacy_file_reference      varchar(255) nullable
  remarks                    text nullable
  legacy_payload             json nullable
  created_at / updated_at / deleted_at
```

Constraints and indexes:

- Unique `(legacy_source, legacy_id)` when both values are present.
- Unique normalized `asset_tag` when populated. Duplicate legacy tags are reported and resolved before this constraint is enabled.
- Index `(asset_type, status)`, `location_id`, `assigned_official_id`, `warranty_expiry`, `end_of_support`, and `amc_end`.
- `asset_type` and `status` are stored as strings and cast to backed PHP enums. Database enums are not used.
- Serial-number uniqueness is not assumed globally. Duplicate reporting groups by manufacturer, type, and normalized serial number for operator review.

### 4.2 Asset assignment history

Current assignment alone is insufficient for inventory accountability.

```text
asset_assignments
  id
  asset_id
  official_id nullable
  assigned_by user_id nullable
  assigned_at
  returned_at nullable
  return_recorded_by user_id nullable
  source: application | legacy_import
  condition_out nullable
  condition_in nullable
  remarks nullable
  timestamps
```

Only one open assignment may exist per asset. Assignment and return operations run in transactions and lock the asset row.

### 4.3 Consumables and immutable stock ledger

```text
consumables
  id, name, sku nullable, unit
  in_stock unsigned integer
  min_quantity unsigned integer nullable
  max_quantity unsigned integer nullable
  timestamps, deleted_at

entries
  id
  consumable_id
  type: purchase | issue | adjustment_in | adjustment_out
  quantity unsigned integer
  stock_after unsigned integer
  recipient_official_id nullable
  recorded_by user_id
  remarks nullable
  idempotency_key nullable unique
  legacy_id nullable unique
  created_at
```

Ledger entries are immutable. Corrections are made with compensating adjustment entries, never by updating or deleting historical entries.

`PostStockEntry` must:

1. Validate a positive integer quantity and all referenced records.
2. Begin a database transaction.
3. Load the consumable using `lockForUpdate()`.
4. Reject an issue or outward adjustment that would make stock negative.
5. Update `in_stock` and insert the entry with the resulting `stock_after`.
6. Commit once both writes succeed.

Database checks enforce non-negative quantities and stock where supported. Application invariants are still tested because database checks do not replace domain validation.

### 4.4 Agreements and payment schedules

```text
agreements
  id, name, agency, file_id nullable
  type
  expiry nullable
  annual_cost decimal(15,2) nullable
  currency char(3), default INR
  billing_interval_months nullable
  billing_anchor_date nullable
  paid_till nullable
  remarks nullable
  legacy_payload json nullable
  timestamps, deleted_at

payments
  id, agreement_id
  amount decimal(15,2) nullable
  currency char(3), default INR
  due_date
  paid_date nullable
  status: pending | completed | cancelled
  invoice_number nullable
  remarks nullable
  schedule_key varchar(191) unique
  completed_by user_id nullable
  legacy_id nullable unique
  timestamps
```

`GeneratePaymentSchedule` is an idempotent service. `schedule_key` is deterministically derived from the agreement and due date. Existing legacy payments are imported exactly as stored and are not regenerated.

For new agreements:

- `billing_interval_months` must be an integer from 1 through 12.
- A billing anchor date is required before automatic generation.
- End-of-month anchors remain at the end of subsequent months.
- Schedule preview is shown before agreement changes create, cancel, or move pending payments.
- Completed payments are never silently rewritten.
- Generation is invoked by a command/job or a protected POST action, never by GET.

### 4.5 File registry and attachments

The legacy `files` table describes administrative file records, not uploaded binaries. Preserve that concept and model uploads separately.

```text
files
  id, name
  efile_number nullable
  physical_name nullable
  physical_number nullable
  subject
  opened_at nullable
  division
  legacy_id nullable unique
  timestamps, deleted_at

attachments
  id
  attachable_type / attachable_id
  disk, path, original_name, mime_type, size, checksum
  uploaded_by user_id
  timestamps, deleted_at
```

Attachments use a private disk and authorized download responses. Uploads are restricted by allow-listed MIME type and size, use server-generated paths, and are eligible for malware scanning before release to other users.

### 4.6 Personnel, tasks, and master data

- `officials` preserves legacy `name`, `title`, `designation`, and `location_id`; nullable `email`, `department`, and `phone` may be added.
- `developers` preserves all existing fields, fixes foreign keys to `devcats` and `users`, and stores sensitive profile fields encrypted at rest. Access to personal fields requires a dedicated permission.
- `devcats` preserves legacy `name`, `salary`, `exp`, `dev`, `collab`, and `qualification`. String-encoded salary and experience values are normalized only after source profiling; the original values remain recoverable.
- `tasks` maps `name` to `title` and preserves `priority`, `file_id`, `status`, and `remark`; it adds nullable `description`, `assigned_to`, and `due_date`.
- `locations` and `manufacturers` retain every legacy field and gain normalized search columns only where useful.
- Models use explicit casts and `$fillable` fields aligned with migrations and Form Requests.

### 4.7 Audit history

Activity logging records the authenticated actor, changed fields, old and new values, subject, timestamp, and request correlation ID. Secrets, authentication fields, encrypted personnel fields, and attachment contents are excluded.

Audit records are append-only to normal users. Retention and archival periods are configuration-driven and documented before production deployment.

---

## 5. Verified Legacy Data Mapping

The repository migrations are only a starting point; the real production schema and row counts must be inspected before the mapping is frozen. Unexpected production columns are preserved in `legacy_payload` and reported.

Every imported asset stores the complete original source row in `legacy_payload` in addition to the normalized mappings below.

| Source | Legacy field | Target |
| :--- | :--- | :--- |
| desktops | `id` | `legacy_id`; `legacy_source = desktop` |
| desktops | `serial` | `serial_number` |
| desktops | `brand` | resolve/create manufacturer; also preserve in `manufacturer_name_legacy` |
| desktops | `category` | `specifications.category` and name fallback |
| desktops | `location_id` | validated `location_id` |
| desktops | `active` | `status`: in-use or decommissioned |
| desktops | `file` | `legacy_file_reference` |
| desktops | `purchased` | `purchase_date` |
| laptops | `id` | `legacy_id`; `legacy_source = laptop` |
| laptops | `serial`, `brand`, `category` | same normalization as desktops |
| laptops | `official_id` | `assigned_official_id` plus an initial assignment-history row |
| laptops | `active` | `status` |
| laptops | `file`, `purchased` | `legacy_file_reference`, `purchase_date` |
| servers | `id` | `legacy_id`; `legacy_source = server` |
| servers | `asset_sr` | `serial_number` |
| servers | `asset_tag` | normalized `asset_tag` |
| servers | `location` | `location_text_legacy` |
| servers | `covered` | `specifications.support_covered` |
| servers | `purchase_date`, `purchase_cost` | typed purchase fields |
| servers | `description` | `name` fallback and `description` |
| servers | `end_of_sale`, `end_of_support` | matching typed fields |
| servers | `contract_expiry` | `amc_end` |
| servers | `amc_cost`, `contract_type`, `contracthash` | matching contract fields |
| servers | `address`, `city`, `state`, `country`, `terms`, `duplicated`, `timeout` | named keys in `legacy_payload` |
| devices | `id` | `legacy_id`; `legacy_source = device` and `asset_type = switch` |
| devices | `oem` | resolve/create manufacturer and preserve raw value |
| devices | `sublocation`, `location` | combined `location_text_legacy`; both raw values retained |
| devices | `partcode`, `serial` | `part_code`, `serial_number` |
| devices | `purchase_date`, `cost` | typed purchase fields |
| devices | `support_date` | `end_of_support` |
| devices | `amc_start`, `amc_end`, `amc_cost` | matching AMC fields |
| devices | `remark` | `remarks` |
| storages | `id` | `legacy_id`; `legacy_source = storage` |
| storages | `description` | `name` and `description` |
| storages | `serial` | `serial_number` |
| storages | `warranty_end` | `warranty_expiry` |
| storages | `location_id` | validated `location_id` |
| storages | `location` | `location_text_legacy` |
| storages | `type` | `specifications.storage_type` |

No mapping may refer to a source column until it has been verified in the actual source schema.

---

## 6. Migration and Cutover Strategy

### 6.1 Import command

Provide a command with explicit operational modes:

```text
php artisan inventory:import-legacy --dry-run
php artisan inventory:import-legacy --resume
php artisan inventory:import-legacy --verify
```

The command uses per-table importer classes, chunks large tables, records checkpoints, and writes a structured anomaly report. A `legacy_import_runs` table stores run ID, source fingerprint, started/completed timestamps, per-table counts, checkpoint, status, and error summary.

Import order:

1. Users with forced password reset or administrator-issued invitations
2. Locations, manufacturers, devcats, and file registry records
3. Officials and developers
4. Assets and initial assignment history
5. Consumables
6. Stock ledger entries followed by balance reconciliation
7. Agreements
8. Existing payments
9. Tasks
10. Activity history only if the source data is reliable and privacy-approved

### 6.2 Idempotency and anomaly handling

- Upsert imported rows by `(legacy_source, legacy_id)` or the table-specific `legacy_id`.
- A rerun must not duplicate records or ledger effects.
- Missing foreign keys are recorded as anomalies; they are not silently replaced with arbitrary default IDs.
- Duplicate asset tags, invalid dates, malformed amounts, negative stock histories, and unknown enum values are reported for an operator decision.
- Raw source values remain in `legacy_payload` even after normalization.

### 6.3 Verification gates

“Zero data loss” means all of the following pass:

1. Every source row is represented by a target row or an explicitly approved quarantine record.
2. Every source field is either mapped to a typed target field or retained in `legacy_payload`.
3. Per-table row counts and deterministic checksums reconcile.
4. No unreported orphaned foreign keys remain.
5. Agreement and payment totals reconcile by agreement and globally.
6. The final consumable balance agrees with the imported ledger, or an approved opening-balance adjustment documents the difference.
7. Every imported asset can be traced back to its source table and ID.
8. A sample selected by business owners passes field-by-field comparison.

Record-count parity alone is not sufficient.

### 6.4 Cutover and rollback

1. Produce and test a restorable backup of the legacy database and attachments.
2. Rehearse the entire import and verification process on a recent snapshot.
3. Obtain user acceptance against the rehearsed environment.
4. Begin a maintenance window and make the legacy application read-only.
5. Run the final incremental/full import and all verification gates.
6. Switch application traffic only after technical and business sign-off.
7. Keep the legacy database read-only for the approved retention period.

Rollback switches traffic back to the untouched legacy application/database. Legacy tables are never dropped automatically; archival or deletion is a separate, approved operation after the retention period.

---

## 7. Authentication, Authorization, and Privacy

Public registration is disabled. Accounts are created by administrators or through expiring invitations. Email verification, rate limiting, secure password-reset behavior, and two-factor authentication for privileged roles are enabled.

Roles and permissions are database-backed and enforced through Laravel policies. The implementation may use a maintained compatible package or small first-party role/permission tables, but authorization rules must not be hardcoded only in views or route names.

### 7.1 Roles

| Capability | Administrator | Inventory manager | Stock operator | Finance operator | Viewer | Auditor |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| Manage users and roles | Yes | No | No | No | No | No |
| Create/update/decommission assets | Yes | Yes | No | No | No | No |
| Assign and return assets | Yes | Yes | No | No | No | No |
| Purchase/issue stock | Yes | Yes | Yes | No | No | No |
| Manage agreements | Yes | Yes | No | Yes | No | No |
| Complete/cancel payments | Yes | No | No | Yes | No | No |
| View ordinary inventory | Yes | Yes | Yes | Yes | Yes | Yes |
| Export data | Yes | Yes | Scoped | Scoped | No | Read-only audit exports |
| View sensitive personnel fields | Yes | Explicit permission | No | No | No | Explicit permission |
| View audit history | Yes | Scoped | Own actions | Scoped | No | Yes |

Policies enforce permissions server-side for every action, including reactive-component requests, exports, downloads, and bulk operations. Hiding a UI control is not authorization.

### 7.2 Privacy remediation

Before public or production use:

- Replace committed real-looking personnel and organizational seed data with deterministic fictional fixtures.
- Remove the known seeded password/account and rotate credentials if it has ever been deployed.
- Assess and, where required, purge sensitive data from Git history.
- Encrypt approved sensitive personnel fields at rest and redact them from logs and exports.
- Define retention, access-review, breach-response, and deletion procedures.

---

## 8. HTTP and Routing Design

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('assets/exports', AssetExportController::class)
        ->name('assets.exports.store');
    Route::resource('assets', AssetController::class)->except('destroy');
    Route::post('assets/{asset}/assign', [AssetAssignmentController::class, 'store'])
        ->name('assets.assign');
    Route::post('assets/{asset}/return', [AssetReturnController::class, 'store'])
        ->name('assets.return');
    Route::patch('assets/{asset}/decommission', DecommissionAssetController::class)
        ->name('assets.decommission');

    Route::resource('consumables', ConsumableController::class)->except('destroy');
    Route::post('consumables/{consumable}/entries', [EntryController::class, 'store'])
        ->name('consumables.entries.store');
    Route::get('stock', [EntryController::class, 'index'])->name('stock.index');

    Route::post('agreements/exports', AgreementExportController::class)
        ->name('agreements.exports.store');
    Route::resource('agreements', AgreementController::class);
    Route::resource('payments', PaymentController::class)->except(['create', 'store', 'destroy']);

    Route::resource('officials', OfficialController::class);
    Route::resource('developers', DeveloperController::class);
    Route::resource('tasks', TaskController::class);
    Route::resource('files', FileController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('manufacturers', ManufacturerController::class);
});
```

The exact export method may enqueue a job and return a status/download page for large datasets. State changes use POST, PUT, PATCH, or DELETE with CSRF protection; never GET.

---

## 9. User Experience

### 9.1 Navigation

- Dashboard
- IT assets: all, desktops, laptops, servers, switches, storage, decommissioned
- Consumables: inventory, purchases/issues, adjustments
- Contracts and AMCs: agreements, scheduled payments
- Personnel: officials and developers
- Master data: locations, manufacturers, developer categories
- Tasks
- File registry and attachments
- Activity log, permission-gated

### 9.2 Dashboard

- Total assets by status and type
- Assets assigned versus available
- Agreements expiring in 30 and 180 days
- Warranties, support, and AMC dates approaching expiry
- Consumables at or below their configured minimum
- Pending and overdue payments
- Recent authorized activity

Every dashboard count and destination list must use the same query scope so counts cannot disagree with detail pages.

### 9.3 Tables, search, and exports

Filtering, sorting, and pagination are server-side and represented in query parameters. Exports reuse the same validated query/filter object, ensuring that the exported dataset matches the current filtered view.

Global lookup searches normalized asset tag and serial-number columns. Results remain permission-scoped. Large exports run as queued jobs and expire after a configured retention period.

### 9.4 Accessibility and responsive behavior

- Keyboard-accessible navigation and dialogs
- Visible focus indicators
- Proper labels and validation summaries
- Semantic tables and headings
- Sufficient color contrast
- Mobile and narrow-screen support
- No workflow depends solely on color or hover state

---

## 10. Testing and Quality Assurance

### 10.1 Automated tests

- Authentication, verification, throttling, invitation, and 2FA behavior
- Role and policy tests for every resource, export, download, and sensitive field
- Asset CRUD, assignment, return, decommissioning, scopes, filters, and duplicate detection
- Stock purchase, issue, adjustment, insufficient-stock rejection, idempotency, rollback, and concurrent requests
- Agreement validation, schedule generation, end-of-month behavior, retries, agreement changes, and completed-payment protection
- Private attachment upload, validation, authorization, and download
- Dashboard count/list consistency
- XLSX contents and permission-scoped exports
- Fresh install migrations and seeders
- Legacy importer dry-run, resume, retry, idempotency, anomaly reporting, and field-level reconciliation

### 10.2 Migration fixtures

Maintain versioned, anonymized fixtures that represent:

- Normal legacy data
- Missing foreign keys
- Duplicate asset tags and serials
- Invalid or zero dates
- Null and malformed values
- Conflicting IDs across the five asset tables
- Inconsistent stock balances
- Duplicate or incomplete payments
- Unexpected additional legacy columns

Importer integration tests run against MySQL because SQLite does not reproduce all foreign-key, locking, decimal, JSON, and concurrency behavior.

### 10.3 CI quality gates

Every pull request must run:

1. Composer validation and dependency installation from lock files
2. `composer audit` and `npm audit` according to an approved severity policy
3. Laravel Pint in check mode
4. Larastan/PHPStan at the agreed level with no unbaselined errors
5. Frontend linting and the production frontend build
6. Unit and feature tests
7. MySQL integration, migration, importer, and concurrency tests
8. Coverage reporting with thresholds focused on domain services and policies

The main branch must remain deployable. CI uses supported PHP and Node versions and pinned major versions of GitHub Actions.

---

## 11. Operations and Deployment

- Provide documented local setup using Docker/Sail or an equivalent reproducible environment.
- Maintain accurate `.env.example` values without credentials.
- Store production secrets in the deployment platform, never in Git.
- Run migrations as a distinct deployment step with backups and rollback instructions.
- Deploy queue workers and the scheduler with process supervision.
- Expose authenticated application health plus platform-level liveness/readiness checks.
- Centralize structured logs with request correlation IDs and alerting for failed jobs, imports, exports, mail, and repeated authorization failures.
- Back up the database and private attachments; regularly test restoration.
- Document recovery-point and recovery-time objectives.
- Configure HTTPS, secure cookies, trusted hosts/proxies, security headers, and restricted storage access.
- Schedule activity-log and generated-export retention cleanup.

---

## 12. Delivery Phases

### Phase 0: Discovery and safety

- Inventory the actual production schema, row counts, database engine/version, attachments, and deployment environment.
- Freeze the mapping specification based on observed data.
- Back up and anonymize data used outside production.
- Remove known credentials and plan Git-history remediation.

### Phase 1: Foundation

- Create the application from the approved platform baseline and provide a reproducible development environment.
- Establish authentication, invitation flow, roles, policies, CI, static analysis, formatting, and audit logging.
- Create clean fresh-install migrations.

### Phase 2: Core domains

- Implement master data, file registry, attachments, personnel, assets, and assignment history.
- Implement immutable consumable ledger and concurrency protections.
- Implement agreements and idempotent payment schedules.

### Phase 3: Importer

- Implement dry-run, checkpointing, idempotency, mapping, quarantine, and reconciliation reports.
- Exercise the importer against all anonymized fixtures and a production-sized snapshot.

### Phase 4: UI and reporting

- Complete dashboard, server-side tables, global lookup, exports, activity views, accessibility, and responsive behavior.

### Phase 5: Rehearsal and cutover

- Restore a current snapshot, run the complete migration, verify, performance-test, and conduct user acceptance.
- Execute the approved production cutover and retain the read-only legacy system for rollback.

---

## 13. Acceptance Criteria

The modernization is complete only when:

1. A fresh checkout can be installed, migrated, seeded with fictional data, built, and tested from documented commands.
2. The selected Laravel/PHP baseline satisfies the required support runway, and runtime dependencies have no unresolved high-severity advisories.
3. All routes require the intended authentication and policy authorization.
4. Public registration and known default credentials are absent.
5. All advertised CRUD and workflow actions are implemented; no routed controller action is a stub.
6. Stock cannot become negative, ledger writes are atomic and immutable, and concurrency tests pass on MySQL.
7. Payment generation is deterministic and idempotent, with no state-changing GET routes.
8. Every legacy row and field passes the verification gates or appears in an explicitly approved quarantine report.
9. Business owners approve a field-by-field sample and financial/stock reconciliation.
10. Personal data is removed from normal fixtures, protected in production, and excluded from unauthorized exports and logs.
11. Server-side filters, counts, pages, and exports return consistent datasets.
12. CI passes formatting, static analysis, frontend build, security audit, unit, feature, integration, migration, and importer checks.
13. Backup restoration and cutover rollback have both been rehearsed successfully.
14. Deployment, monitoring, queue/scheduler, retention, backup, and recovery documentation is complete.

---

## 14. Open Decisions Required Before Implementation

These decisions must be resolved during Phase 0 and recorded as amendments:

1. Confirm the platform baseline, MySQL hosting constraints, cache/lock infrastructure, queues, and private file storage.
2. Confirm which users may see developer contact and personal-profile fields.
3. Confirm whether historical activity logs are legally and operationally appropriate to import.
4. Define asset-tag normalization and duplicate-resolution rules.
5. Define whether manufacturer names from legacy `brand`/`oem` values should be automatically merged or reviewed.
6. Define the authoritative opening stock when legacy ledger history disagrees with `consumables.in_stock`.
7. Define payment schedule anchors and treatment of already-overdue legacy payments.
8. Approve retention periods for the legacy database, audit logs, attachments, and generated exports.
9. Approve maintenance-window length, rollback threshold, recovery-point objective, and recovery-time objective.

---

## 15. Architecture Change Rule

This specification is intentionally independent of Laravel major versions. A framework release, starter-kit replacement, bundler change, or package-major upgrade does not require an architecture revision when all capability requirements, contracts, invariants, and acceptance criteria remain satisfied.

Revise this specification only when one of those stable requirements changes. Record implementation-version changes, compatibility findings, and support dates in the platform baseline instead.
