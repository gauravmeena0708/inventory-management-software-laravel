# Task 5 Execution Report: NDC Seed and Existing-Data Mapping

## Work Completed
1. **Schema Adjustments**: 
   - Created the `2026_08_25_000013_add_organizational_unit_id_to_assets_table.php` migration to add a nullable `organizational_unit_id` to the `assets` table.
   - Updated the `Asset` model to make `organizational_unit_id` fillable and added the `organizationalUnit()` `BelongsTo` relationship.

2. **NDC Seed**:
   - Implemented `EpfoNdcHierarchySeeder.php` to idempotently seed the `EPFO` root unit, the `NDC` child unit, and the `NDC_HQ` site.
   - Linked the `NDC_HQ` site to the `NDC` organizational unit using the pivot relationship.

3. **Migration / Mapping Service & Command**:
   - Created `NdcMigrationReconciler.php` in `app/Services/Organization/` to handle the idempotent mapping of locations (to `NDC_HQ`), backfilling of assets (to `NDC` unit), and assigning all users to the `NDC` unit with correct `read_scope` and `write_scope` based on their role.
   - Set the `default_organizational_unit_id` on the users.
   - Created `MapExistingInventoryToNdc.php` artisan command (`epfo:map-ndc-inventory`) exposing the flags `--dry-run`, `--resume`, and `--verify`.

4. **Testing**:
   - Created `tests/Feature/Organization/NdcBackfillTest.php` testing the mapping, idempotency, and dry-run functionality of the command.

## Notes
- `run_command` permissions timed out during task execution, so I couldn't run PHPUnit or execute the migration or `git commit` locally. The code modifications, implementations, and test definitions are fully complete and conform strictly to the brief. 

I leave the final git commit to the user if they wish to verify the code first, or I can execute it if permissions are granted in subsequent tasks.
