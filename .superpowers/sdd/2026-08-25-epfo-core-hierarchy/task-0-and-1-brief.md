# Task 0 & 1 Brief: Preflight and Organizational-Unit Schema

**Source Plan:** docs/superpowers/plans/2026-08-25-epfo-core-hierarchy.md

## Task 0: Preflight & Contract Tests
1. Create `tests/Feature/Architecture/CurrentSchemaContractTest.php`. Write tests to assert that `users` lacks a `location_id` column, and verify the current state of `assets` and `locations` tables.
2. Create `tests/Feature/Architecture/LegacyImporterContractTest.php`. Write a test to assert that the legacy database connection configuration exists and is properly isolated/read-only (or at least configuration expectations match).
3. Run `php artisan test` to ensure existing baseline tests pass.

## Task 1: Organizational-Unit Schema
1. Create Enum `app/Enums/OrganizationalUnitType.php` with values: `ROOT`, `HEAD_OFFICE`, `NDC`, `ZONAL_OFFICE`, `REGIONAL_OFFICE`, `DISTRICT_OFFICE`, `SPECIAL_STATE_OFFICE`, `VIGILANCE_HQ`, `VIGILANCE_ZVD`, `PDUNASS`, `ZTI`.
2. Create migration `database/migrations/2026_08_25_000010_create_organizational_units_table.php`. 
   - Columns: `id`, `parent_id` (nullable FK to id), `code` (string, unique), `name` (string), `unit_type` (string), `path` (string, indexed, nullable), `depth` (unsignedInteger), `is_active` (boolean, default true), `metadata` (json, nullable), `timestamps`, `softDeletes`.
3. Create Model `app/Models/OrganizationalUnit.php`.
   - Casts: `unit_type` to `OrganizationalUnitType::class`, `is_active` to bool, `metadata` to array.
   - Relations: `parent()` (belongsTo), `children()` (hasMany).
   - **Crucial:** Do NOT add automatic subtree rewriting to model events (no boot method generating paths).
4. Create `database/factories/OrganizationalUnitFactory.php`.
5. Create `tests/Feature/Organization/OrganizationalUnitSchemaTest.php` to verify columns, enum casts, FKs, unique code constraint, and that rollback works.

**Constraints:** 
- Do NOT run `migrate:fresh` globally. Use the `RefreshDatabase` trait inside your tests.
- When you are done, run your new tests to ensure they pass.

Write your execution report to `.superpowers/sdd/2026-08-25-epfo-core-hierarchy/task-0-1-report.md`.
