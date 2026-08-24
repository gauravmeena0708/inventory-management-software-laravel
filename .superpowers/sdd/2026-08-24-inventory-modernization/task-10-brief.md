# Task 10 Brief: HTTP Layer, Controllers, Form Requests & Routes

## Objective
Implement all RESTful controllers, Form Requests, authorization policy checks, route registrations in `routes/web.php`, and feature tests in `tests/Feature/HttpRoutesTest.php`.

## Specific Requirements
1. **Form Requests (`app/Http/Requests/`)**:
   - `StoreAssetRequest.php` & `UpdateAssetRequest.php`: Validates name, asset_type (Enum), serial_number, manufacturer_id, location_id, assigned_official_id, purchase_date, warranty_expiry, specifications array.
   - `AssignAssetRequest.php`: Validates official_id (exists:officials,id), remarks.
   - `StoreConsumableRequest.php`: Validates name, unit, in_stock (numeric >= 0), min_quantity (numeric >= 0).
   - `StoreEntryRequest.php`: Validates type (Enum), quantity (integer >= 1), recipient_official_id (required if type=issue), remarks, idempotency_key.
   - `StoreAgreementRequest.php`: Validates name, agency, type, annual_cost, billing_interval_months, billing_anchor_date, expiry.
   - `CompletePaymentRequest.php`: Validates invoice_number, paid_date.
2. **Controllers (`app/Http/Controllers/`)**:
   - `DashboardController.php`: Computes KPIs (total assets, assigned vs available, agreements expiring in 30/180 days, low stock count, overdue/pending payments count, recent audit logs) and returns `dashboard` view.
   - `AssetController.php`:
     - `index`: Filters by `type`, `status`, `location_id`, keyword search, returns paginated assets.
     - `create`, `store` (uses `CreateAssetAction`), `show`, `edit`, `update` (uses `UpdateAssetAction`).
     - `export`: Uses `TabularExporter` and `AssetsExport` to stream Excel download.
   - `AssetAssignmentController.php`:
     - `assign`: Uses `AssignAssetAction`.
     - `return`: Uses `ReturnAssetAction`.
     - `decommission`: Uses `DecommissionAssetAction`.
   - `ConsumableController.php`: Standard CRUD.
   - `EntryController.php`:
     - `index`: Lists stock ledger entries.
     - `store`: Uses `PostStockEntryAction`.
   - `AgreementController.php`:
     - CRUD + `export` via `AgreementsExport` + invokes `GeneratePaymentScheduleAction` on creation.
   - `PaymentController.php`:
     - `index`: Filter by status (`pending`, `completed`, `overdue`).
     - `complete`: Uses `CompletePaymentAction`.
     - `cancel`: Cancels pending payment.
   - `OfficialController.php`, `DeveloperController.php`, `TaskController.php`, `FileRecordController.php`, `LocationController.php`, `ManufacturerController.php`.
3. **Routing (`routes/web.php`)**:
   - Register all resource routes and actions under `['auth']` middleware group as defined in Spec Section 8.
4. **Tests (`tests/Feature/HttpRoutesTest.php`)**:
   - Test dashboard access and KPI response.
   - Test asset creation, filtering by type, assignment, and export endpoint.
   - Test consumable creation and stock issuance via `EntryController`.
   - Test agreement creation and payment completion endpoints.
   - Test unauthorized role receives 403 on protected mutating endpoints.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-10-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
