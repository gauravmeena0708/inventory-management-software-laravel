# Task 4 Brief: Unified Asset Domain & Assignment History

## Objective
Implement the unified `Asset` model, `AssetAssignment` model, `AssetType` and `AssetStatus` enums, database migration for `assets` and `asset_assignments`, service action classes (`CreateAssetAction`, `UpdateAssetAction`, `AssignAssetAction`, `ReturnAssetAction`, `DecommissionAssetAction`), and comprehensive domain tests in `tests/Feature/AssetDomainTest.php`.

## Specific Requirements
1. **Enums**:
   - `app/Enums/AssetType.php`: `DESKTOP = 'desktop'`, `LAPTOP = 'laptop'`, `SERVER = 'server'`, `SWITCH = 'switch'`, `STORAGE = 'storage'`, `OTHER = 'other'`. Include labels/values helpers.
   - `app/Enums/AssetStatus.php`: `IN_USE = 'in_use'`, `IN_STOCK = 'in_stock'`, `UNDER_MAINTENANCE = 'under_maintenance'`, `DECOMMISSIONED = 'decommissioned'`. Include labels/colors helpers.
2. **Migrations**:
   - `database/migrations/2026_08_24_000003_create_assets_and_assignments_tables.php`:
     - Create `assets` table with all fields from Spec Section 4.1 (`legacy_source`, `legacy_id`, `asset_tag`, `name`, `asset_type`, `manufacturer_id`, `manufacturer_name_legacy`, `location_id`, `location_text_legacy`, `assigned_official_id`, `status`, `serial_number`, `model_number`, `part_code`, `ip_address`, `mac_address`, `operating_system`, `description`, `specifications` json, `purchase_date`, `purchase_cost`, `currency`, `warranty_expiry`, `end_of_sale`, `end_of_support`, `amc_start`, `amc_end`, `amc_cost`, `contract_type`, `contract_reference`, `file_id`, `legacy_file_reference`, `remarks`, `legacy_payload` json, timestamps, softDeletes). Indexes on `(asset_type, status)`, `serial_number`, `asset_tag`, `location_id`, `assigned_official_id`, `warranty_expiry`, `amc_end`.
     - Create `asset_assignments` table from Spec Section 4.2 (`id`, `asset_id`, `official_id`, `assigned_by`, `assigned_at`, `returned_at`, `return_recorded_by`, `source`, `condition_out`, `condition_in`, `remarks`, timestamps).
3. **Models**:
   - `app/Models/Asset.php`:
     - Casts: `asset_type => AssetType::class`, `status => AssetStatus::class`, `specifications => 'array'`, `legacy_payload => 'array'`, `purchase_date => 'date'`, `warranty_expiry => 'date'`, `amc_start => 'date'`, `amc_end => 'date'`, `end_of_support => 'date'`.
     - Relations: `manufacturer()`, `location()`, `assignedOfficial()`, `file()`, `assignments()`, `currentAssignment()`, `attachments()`.
     - Scopes: `scopeType($query, $type)`, `scopeStatus($query, $status)`, `scopeInUse($query)`, `scopeInStock($query)`, `scopeExpiringAmc($query, $days = 180)`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
   - `app/Models/AssetAssignment.php`:
     - Relations: `asset()`, `official()`, `assignedBy()`, `returnedBy()`.
     - Scope: `scopeOpen($query)`.
4. **Service Actions (`app/Services/Assets/`)**:
   - `CreateAssetAction.php`: Creates asset record and triggers audit log.
   - `UpdateAssetAction.php`: Updates asset details.
   - `AssignAssetAction.php`: Runs in DB transaction with `lockForUpdate()`, closes any prior open assignment, creates new `AssetAssignment`, updates `assigned_official_id` and sets `status = AssetStatus::IN_USE`.
   - `ReturnAssetAction.php`: Runs in DB transaction with `lockForUpdate()`, closes open `AssetAssignment` with `returned_at` and `return_recorded_by`, unsets `assigned_official_id` and sets `status = AssetStatus::IN_STOCK`.
   - `DecommissionAssetAction.php`: Runs in DB transaction, closes open assignment, sets `status = AssetStatus::DECOMMISSIONED`.
5. **Tests**:
   - `tests/Feature/AssetDomainTest.php`:
     - Test asset creation, updating, and type/status scopes.
     - Test assignment workflow and assignment history record creation.
     - Test return workflow and assignment closure.
     - Test decommissioning.
     - Test concurrent/transactional safety.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-4-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
