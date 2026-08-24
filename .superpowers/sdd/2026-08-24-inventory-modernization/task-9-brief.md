# Task 9 Brief: Read-Only Legacy Data Importer & Reconciler

## Objective
Implement the read-only legacy data importer, per-table chunked mappers, `legacy_payload` preservation, `LegacyImportRun` tracking, `ReconciliationReporter`, the Artisan CLI command `php artisan inventory:import-legacy {--dry-run} {--resume} {--verify}`, and feature tests in `tests/Feature/LegacyImporterVerificationTest.php`.

## Specific Requirements
1. **Migration & Model**:
   - `database/migrations/2026_08_24_000007_create_legacy_import_runs_table.php`:
     - `legacy_import_runs`: `id`, `source_fingerprint` (string, nullable), `dry_run` (boolean), `started_at` (timestamp), `completed_at` (timestamp, nullable), `counts` (json, nullable), `anomalies` (json, nullable), `status` (string, default 'running'), `error_summary` (text, nullable), `timestamps`.
   - `app/Models/LegacyImportRun.php`: `$fillable`, `casts => ['counts' => 'array', 'anomalies' => 'array', 'dry_run' => 'boolean']`.
2. **Table Importers (`app/Services/Importer/TableImporters/`)**:
   - `MasterDataTableImporter.php`: Imports locations, manufacturers, devcats, file registry records from `legacy` connection.
   - `PersonnelTableImporter.php`: Imports officials, users (assigning viewer role with forced reset flag), developers.
   - `AssetTableImporter.php`:
     - Imports from legacy `desktops`, `laptops`, `servers`, `devices`, and `storages` into unified `assets` table.
     - Sets `legacy_source` (`desktop`, `laptop`, `server`, `device`, `storage`) and `legacy_id`.
     - Preserves full original row in `legacy_payload` (JSON).
     - Creates corresponding `AssetAssignment` row if `official_id` is assigned.
   - `ConsumableTableImporter.php`:
     - Imports `consumables` and `entries` (mapping `legacy_id`), verifies stock balances.
   - `AgreementTableImporter.php`:
     - Imports `agreements` and existing `payments` (mapping `legacy_id`).
   - `TaskTableImporter.php`:
     - Imports `tasks`.
3. **Manager & Reporter (`app/Services/Importer/`)**:
   - `LegacyImportManager.php`:
     - Orchestrates imports in the order defined in Spec Section 6.1 (Users/Master Data $\rightarrow$ Personnel $\rightarrow$ Assets $\rightarrow$ Consumables $\rightarrow$ Entries $\rightarrow$ Agreements $\rightarrow$ Payments $\rightarrow$ Tasks).
     - Supports `--dry-run` (rolls back DB transaction at the end or runs simulation without writing to target), `--resume`, and `--verify`.
     - Enforces read-only usage of `legacy` DB connection.
   - `ReconciliationReporter.php`:
     - Calculates source vs target row counts, unmapped records, missing foreign keys, stock discrepancy checks, financial totals parity.
4. **Artisan Command**:
   - `app/Console/Commands/ImportLegacyInventoryCommand.php`:
     - Signature: `inventory:import-legacy {--dry-run : Simulate the import without committing changes} {--resume : Resume from previous run} {--verify : Run reconciliation verification only}`
     - Prints progress bars, summary table of imported counts, anomalies, and reconciliation verification status.
5. **Tests**:
   - `tests/Feature/LegacyImporterVerificationTest.php`:
     - Test importer reads legacy tables (`desktops`, `laptops`, `servers`, `devices`, `storages`, `consumables`, `entries`, `agreements`, `payments`) and maps them into target tables.
     - Test `legacy_payload` retains unmapped raw attributes.
     - Test `--dry-run` mode leaves target database empty.
     - Test reconciliation report detects count parity and flag anomalies.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-9-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
