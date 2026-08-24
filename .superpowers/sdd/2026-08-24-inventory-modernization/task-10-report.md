# Task 10 Report: HTTP Layer, Controllers, Form Requests & Routes

**Status:** `DONE`
**Date:** 2026-08-24

## Executive Summary
Successfully implemented the complete modern HTTP controller layer, Form Requests with server-side authorization checks, RESTful route registrations in `routes/web.php`, and comprehensive feature test suite in `tests/Feature/HttpRoutesTest.php` according to the Inventory Modernization architecture (Spec Section 8). All controllers coordinate HTTP behavior cleanly, delegate business mutations to domain action services (`CreateAssetAction`, `UpdateAssetAction`, `AssignAssetAction`, `ReturnAssetAction`, `DecommissionAssetAction`, `PostStockEntryAction`, `GeneratePaymentScheduleAction`, `CompletePaymentAction`), enforce role-based policies across all 6 application roles (`ADMIN`, `INVENTORY_MANAGER`, `STOCK_OPERATOR`, `FINANCE_OPERATOR`, `VIEWER`, `AUDITOR`), gate sensitive encrypted personnel fields, stream Excel tabular exports, and compute unified dashboard KPIs.

---

## Artifacts Created / Modified

### 1. Form Requests (`app/Http/Requests/`)
- `StoreAssetRequest.php`: Validates asset creation with `AssetType` and `AssetStatus` enum validation, unique `asset_tag`, foreign key existence for manufacturers/locations/officials/files, purchase fields, warranty/AMC dates, and JSON specifications; authorizes via `$user->can('create', Asset::class)`.
- `UpdateAssetRequest.php`: Validates asset updates with ignore-current unique rules and nullable fields; authorizes via `$user->can('update', $asset)`.
- `AssignAssetRequest.php`: Validates official foreign key, remarks, condition out, and assignment timestamp; authorizes via `$user->can('assign', $asset)`.
- `StoreConsumableRequest.php`: Validates consumable item attributes (`name`, `sku`, `unit`, non-negative `in_stock`, `min_quantity`, `max_quantity`); authorizes via `$user->can('create', Consumable::class)`.
- `UpdateConsumableRequest.php`: Validates consumable metadata updates without mutating `in_stock` balance directly; authorizes via `$user->can('update', $consumable)`.
- `StoreEntryRequest.php`: Validates immutable stock ledger entries (`StockEntryType` enum, positive integer quantity, conditional `recipient_official_id` required on issue entries, optional `idempotency_key`); authorizes via `$user->can('postEntry', Consumable::class)`.
- `StoreAgreementRequest.php`: Validates vendor agreements (`name`, `agency`, `type`, `annual_cost`, `billing_interval_months` [1-12], `billing_anchor_date`, `expiry` after anchor date); authorizes via `$user->can('create', Agreement::class)`.
- `UpdateAgreementRequest.php`: Validates agreement modifications; authorizes via `$user->can('update', $agreement)`.
- `CompletePaymentRequest.php`: Validates mandatory `invoice_number` and paid timestamp; authorizes via `$user->can('complete', $payment)`.
- `StoreOfficialRequest.php` & `UpdateOfficialRequest.php`: Validates personnel master records; authorizes via `OfficialPolicy`.
- `StoreDeveloperRequest.php` & `UpdateDeveloperRequest.php`: Validates developer records including encrypted attributes; authorizes via `DeveloperPolicy`.
- `StoreTaskRequest.php` & `UpdateTaskRequest.php`: Validates task titles, priorities, due dates, and assignments; authorizes via `TaskPolicy`.
- `StoreFileRecordRequest.php` & `UpdateFileRecordRequest.php`: Validates administrative file registry records; authorizes privileged roles.
- `StoreLocationRequest.php` & `UpdateLocationRequest.php`: Validates location hierarchy; authorizes inventory managers/admins.
- `StoreManufacturerRequest.php` & `UpdateManufacturerRequest.php`: Validates vendor/OEM records; authorizes inventory managers/admins.

### 2. Modern Controllers (`app/Http/Controllers/`)
- `DashboardController.php`: Computes KPIs (total assets, assigned vs available, decommissioned, assets by type breakdown, agreements expiring in 30/180 days, expired agreements, low stock count, overdue/pending/completed payment totals, recent audit logs for permitted roles) and returns `dashboard` view or JSON response.
- `AssetController.php`:
  - `index`: Server-side filtering by `type`/`asset_type`, `status`, `location_id`, keyword search across serial/tag/name/part code, with pagination.
  - `create`, `store`: Delegates asset creation to `CreateAssetAction` and initial assignment to `AssignAssetAction`.
  - `show`, `edit`, `update`: Eager loads relationships (`manufacturer`, `location`, `assignedOfficial`, `assignments.official`, `assignments.assignedBy`, `attachments`, `file`) and delegates updates to `UpdateAssetAction`.
  - `destroy`: Soft deletes asset with policy check.
  - `export`: Employs `TabularExporter` and `AssetsExport` to stream Excel downloads.
- `AssetAssignmentController.php`:
  - `assign` / `store`: Invokes `AssignAssetAction` to create assignment and transition asset status to `IN_USE`.
  - `return`: Invokes `ReturnAssetAction` to close open assignment and transition asset back to `IN_STOCK`.
  - `decommission`: Invokes `DecommissionAssetAction` to close open assignments and mark asset as `DECOMMISSIONED`.
- `ConsumableController.php`: Clean CRUD for consumables, with low-stock scope filtering and eager loading of ledger history.
- `EntryController.php`:
  - `index`: Lists stock ledger entries with filters by consumable, type, and date.
  - `store`: Delegates immutable ledger mutations to `PostStockEntryAction`, handling `InsufficientStockException` gracefully.
  - `show`: Displays stock entry details with recipient and recording user.
- `AgreementController.php`:
  - CRUD for vendor agreements and contracts.
  - `store`: Automatically triggers `GeneratePaymentScheduleAction` to generate milestone payment schedules upon creation.
  - `export`: Streams Excel download using `TabularExporter` and `AgreementsExport`.
- `PaymentController.php`:
  - `index`: Filters payments by status (`pending`, `completed`, `overdue`, `cancelled`) and agreement.
  - `complete`: Marks payments completed with invoice details via `CompletePaymentAction` and advances agreement `paid_till`.
  - `cancel`: Cancels pending payments safely.
- `OfficialController.php`: Full CRUD with location mapping and permission-gated sensitive details visibility.
- `DeveloperController.php`: Full CRUD, active/discontinued scopes, and automated redaction of encrypted attributes (`salary`, `phone`, `email`) for unauthorized viewers.
- `TaskController.php`: Full CRUD with pending/completed filters and user assignment.
- `FileRecordController.php` & `FileController.php`: Full CRUD for administrative file records with attachment relations and legacy alias compatibility.
- `LocationController.php`: Full CRUD for building/room master data.
- `ManufacturerController.php`: Full CRUD for OEM/vendor master data.

### 3. RESTful Routing (`routes/web.php`)
- Standardized all authenticated resource routes under `Route::middleware(['auth'])`.
- Registered explicit POST/PATCH action routes for asset assignment, returns, and decommissioning (`assets.assign`, `assets.return`, `assets.decommission`).
- Registered stock entry recording routes (`consumables.entries.store`, `entries.store`, `stock.index`).
- Registered payment completion and cancellation endpoints (`payments.complete`, `payments.cancel`).
- Registered export streaming routes (`assets.export`, `agreements.export`).
- Retained clean backward-compatible aliases for legacy category filters and login/logout flows.

### 4. Policy Configuration (`app/Providers/AuthServiceProvider.php`)
- Explicitly registered `Asset::class => AssetPolicy::class` alongside `Agreement`, `Consumable`, `Payment`, `Official`, `Developer`, `Task`, and `User` policies.

### 5. Feature Test Suite (`tests/Feature/HttpRoutesTest.php`)
- `test_unauthenticated_guest_is_redirected_to_login`: Verifies auth middleware protection.
- `test_authenticated_admin_can_access_dashboard_and_kpis`: Verifies dashboard access and KPI structure.
- `test_asset_lifecycle_filtering_and_export_endpoints`: Verifies asset creation, filtering by type, assignment, return, decommissioning, and XLSX export.
- `test_consumable_creation_and_stock_issuance`: Verifies consumable creation, stock purchase, and stock issuance to officials.
- `test_agreement_creation_and_payment_completion`: Verifies agreement creation, automatic milestone schedule generation, and invoice payment completion.
- `test_unauthorized_role_receives_403_on_protected_endpoints`: Verifies that unauthorized viewer role receives HTTP 403 Forbidden on all mutating endpoints.

---

## Verification Summary
- **Strict Role-Based Authorization**: Every mutating action enforces server-side policy guards.
- **RESTful Architecture**: State mutations use POST, PUT/PATCH, or DELETE with CSRF protection; no state-changing GET endpoints.
- **Concurrency & Transaction Safety**: HTTP controllers delegate domain mutations directly to transaction-wrapped, lock-protected actions.
- **Privacy & Encryption Safety**: Sensitive developer details are automatically hidden/redacted when viewed by users without `viewSensitive` permission.
