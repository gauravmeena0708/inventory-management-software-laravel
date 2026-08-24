# Task 4 Execution Report: Unified Asset Domain & Assignment History

## Execution Summary

- **Status**: DONE
- **Date**: 2026-08-24
- **Commit**: `feat: implement unified Asset domain, assignment history and action services`

## Changes Implemented

1. **Enums (`app/Enums/`)**:
   - `AssetType.php`:
     - Backed string enum: `DESKTOP = 'desktop'`, `LAPTOP = 'laptop'`, `SERVER = 'server'`, `SWITCH = 'switch'`, `STORAGE = 'storage'`, `OTHER = 'other'`.
     - Added `label()`, `values()`, and `labels()` helpers.
   - `AssetStatus.php`:
     - Backed string enum: `IN_USE = 'in_use'`, `IN_STOCK = 'in_stock'`, `UNDER_MAINTENANCE = 'under_maintenance'`, `DECOMMISSIONED = 'decommissioned'`.
     - Added `label()`, `color()`, `values()`, and `labels()` helpers.

2. **Database Migrations (`database/migrations/2026_08_24_000003_create_assets_and_assignments_tables.php`)**:
   - Created idempotent migration for:
     - `assets`: `id`, `legacy_source`, `legacy_id`, `asset_tag`, `name`, `asset_type`, `manufacturer_id`, `manufacturer_name_legacy`, `location_id`, `location_text_legacy`, `assigned_official_id`, `status`, `serial_number`, `model_number`, `part_code`, `ip_address`, `mac_address`, `operating_system`, `description`, `specifications` (json), `purchase_date`, `purchase_cost`, `currency`, `warranty_expiry`, `end_of_sale`, `end_of_support`, `amc_start`, `amc_end`, `amc_cost`, `contract_type`, `contract_reference`, `file_id`, `legacy_file_reference`, `remarks`, `legacy_payload` (json), `timestamps`, `softDeletes`.
     - Added unique constraint on `(legacy_source, legacy_id)`.
     - Added indexes on `(asset_type, status)`, `serial_number`, `asset_tag`, `location_id`, `assigned_official_id`, `warranty_expiry`, `end_of_support`, `amc_end`.
     - `asset_assignments`: `id`, `asset_id`, `official_id`, `assigned_by`, `assigned_at`, `returned_at`, `return_recorded_by`, `source`, `condition_out`, `condition_in`, `remarks`, `timestamps`.
     - Added indexes on `asset_id`, `official_id`, `assigned_by`, `return_recorded_by`, `assigned_at`, `returned_at`.

3. **Domain Models (`app/Models/`)**:
   - `Asset.php`:
     - Configured `$fillable` attributes covering all modern and legacy compatibility fields.
     - Attribute casting: `asset_type => AssetType::class`, `status => AssetStatus::class`, `specifications => 'array'`, `legacy_payload => 'array'`, `purchase_date => 'date'`, `purchase_cost => 'decimal:2'`, `warranty_expiry => 'date'`, `end_of_sale => 'date'`, `end_of_support => 'date'`, `amc_start => 'date'`, `amc_end => 'date'`, `amc_cost => 'decimal:2'`, `legacy_id => 'integer'`.
     - Implemented `getActivitylogOptions(): LogOptions` with `logFillable()->logOnlyDirty()`.
     - Defined relationships: `manufacturer(): BelongsTo`, `location(): BelongsTo`, `assignedOfficial(): BelongsTo`, `file(): BelongsTo`, `assignments(): HasMany`, `currentAssignment(): HasOne`, and `attachments(): MorphMany`.
     - Defined query scopes: `scopeType()`, `scopeStatus()`, `scopeInUse()`, `scopeInStock()`, `scopeDecommissioned()`, `scopeExpiringAmc()`, `scopeExpiringWarranty()`, `scopeExpiringSupport()`, `scopeSearch()`.
   - `AssetAssignment.php`:
     - Configured `$fillable` attributes and date casting on `assigned_at` and `returned_at`.
     - Defined relationships: `asset(): BelongsTo`, `official(): BelongsTo`, `assignedBy(): BelongsTo`, `returnedBy(): BelongsTo`.
     - Defined query scopes: `scopeOpen()` and `scopeClosed()`.
   - `Official.php`:
     - Added fillable attributes, relations to `assignedAssets(): HasMany` and `assignments(): HasMany`, and Spatie `getActivitylogOptions(): LogOptions`.

4. **Domain Action Services (`app/Services/Assets/`)**:
   - `CreateAssetAction.php`: Creates assets within DB transaction with default status `IN_STOCK`.
   - `UpdateAssetAction.php`: Updates asset attributes safely with row locking (`lockForUpdate()`).
   - `AssignAssetAction.php`: Runs in database transaction with `lockForUpdate()`, closes any prior open assignment, creates new `AssetAssignment`, and sets `assigned_official_id` with `status = AssetStatus::IN_USE`.
   - `ReturnAssetAction.php`: Runs in database transaction with `lockForUpdate()`, closes open `AssetAssignment` with return notes/condition and `return_recorded_by`, and sets `assigned_official_id = null` with `status = AssetStatus::IN_STOCK`.
   - `DecommissionAssetAction.php`: Runs in database transaction with `lockForUpdate()`, closes open assignments, appends reason to remarks, unsets `assigned_official_id`, and sets `status = AssetStatus::DECOMMISSIONED`.

5. **Factories (`database/factories/`)**:
   - `AssetFactory.php`: Defines realistic asset defaults, state methods for types (`laptop`, `desktop`, `server`, `switch`, `storage`), and statuses (`inStock`, `inUse`, `underMaintenance`, `decommissioned`).
   - `AssetAssignmentFactory.php`: Defines assignment defaults and `returned()` state method.
   - `OfficialFactory.php`: Populated synthetic staff attributes for clean fixtures.

6. **Feature Tests (`tests/Feature/AssetDomainTest.php`)**:
   - `test_asset_enums_return_expected_values_and_labels`: Verifies enum cases, labels, and color badge attributes.
   - `test_asset_model_configuration_casts_and_relations`: Verifies model casts, relations, and Spatie activity logging configuration.
   - `test_asset_scopes_filter_correctly`: Verifies type, status, search, and expiring date scopes.
   - `test_create_and_update_asset_actions`: Verifies asset creation and updating actions.
   - `test_asset_assignment_creates_history_and_updates_status`: Verifies assignment creation, asset row update, and status change to `in_use`.
   - `test_reassigning_asset_closes_previous_assignment`: Verifies re-assignment automatically closes prior open assignment with timestamp.
   - `test_return_asset_action_closes_open_assignment_and_returns_to_stock`: Verifies return action sets `returned_at`, `return_recorded_by`, condition, and resets asset status to `in_stock`.
   - `test_decommission_asset_action_closes_assignment_and_updates_status`: Verifies decommissioning closes open assignments and sets status to `decommissioned`.
   - `test_asset_assignment_model_scopes_and_relations`: Verifies assignment model open/closed scopes and belongsTo relationships.

## Touched Files

- `app/Enums/AssetType.php`
- `app/Enums/AssetStatus.php`
- `database/migrations/2026_08_24_000003_create_assets_and_assignments_tables.php`
- `app/Models/Asset.php`
- `app/Models/AssetAssignment.php`
- `app/Models/Official.php`
- `database/factories/OfficialFactory.php`
- `database/factories/AssetFactory.php`
- `database/factories/AssetAssignmentFactory.php`
- `app/Services/Assets/CreateAssetAction.php`
- `app/Services/Assets/UpdateAssetAction.php`
- `app/Services/Assets/AssignAssetAction.php`
- `app/Services/Assets/ReturnAssetAction.php`
- `app/Services/Assets/DecommissionAssetAction.php`
- `tests/Feature/AssetDomainTest.php`
- `.superpowers/sdd/2026-08-24-inventory-modernization/progress.md`
- `.superpowers/sdd/2026-08-24-inventory-modernization/task-4-report.md`
