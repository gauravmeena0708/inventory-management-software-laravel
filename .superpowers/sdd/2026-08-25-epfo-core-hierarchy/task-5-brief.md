# Task 5 Brief: NDC Seed and Existing-Data Mapping

**Source Plan:** docs/superpowers/plans/2026-08-25-epfo-core-hierarchy.md

## Task 5: NDC seed and existing-data mapping

You are writing the seeder and mapping commands to idempotently transition the current unstructured location and asset data into the strict National Data Center (NDC) hierarchy. This must be done without breaking any legacy setups or losing original data.

### Files to Create / Modify:
1. `database/seeders/EpfoNdcHierarchySeeder.php`
2. `app/Console/Commands/MapExistingInventoryToNdc.php`
3. `app/Services/Organization/NdcMigrationReconciler.php`
4. `tests/Feature/Organization/NdcBackfillTest.php`
5. Modifying migrations or models if required to support the backfill (like adding nullable `organizational_unit_id` to assets).

### Steps & Requirements:

**1. Schema adjustments:**
- Create a migration to add a nullable `organizational_unit_id` to the `assets` table. Do NOT drop the existing `location_id` on assets, it remains the physical placement.

**2. NDC Seed:**
- Create `EpfoNdcHierarchySeeder.php`.
- It must idempotently seed:
  - An `EPFO` root `OrganizationalUnit`.
  - An `NDC` `OrganizationalUnit` (child of EPFO).
  - An `NDC_HQ` `Site`.
- Link them using the pivot and transactional services to correctly generate paths.

**3. Migration / Mapping Command:**
- Create `app/Console/Commands/MapExistingInventoryToNdc.php`.
- The command must accept flags: `--dry-run`, `--resume`, and `--verify`.
- Implement `NdcMigrationReconciler.php` to handle the heavy lifting. It must:
  - Map all existing `locations` (if they have no parent and no site) to belong to the new `NDC_HQ` site.
  - Backfill `organizational_unit_id` on all existing `assets` to the newly seeded `NDC` unit.
  - Find all users and assign them a membership to the `NDC` unit. **IMPORTANT:** Assign them `read_scope` = 'descendants'. For `write_scope`, ONLY assign 'local' or 'descendants' if their global role allows mutations (e.g., INVENTORY_MANAGER, ADMIN). Viewers/Auditors should get `write_scope` = 'none'. Set `is_primary` = true on the pivot.

**4. Testing:**
- Write `tests/Feature/Organization/NdcBackfillTest.php`.
- Ensure idempotency (running the command twice does not create duplicates).
- Verify the dry-run mode does not commit DB changes.
- Verify user assignments respect the role-to-membership mapping rules.

**5. Verification & Commit:**
- Run the test suite and ensure tests pass.
- Commit changes with message: `feat: implement NDC seed and existing data mapping`

Write your execution report to `.superpowers/sdd/2026-08-25-epfo-core-hierarchy/task-5-report.md`.
