# EPFO Organizational Hierarchy Cutover Runbook

## Purpose

This runbook controls the NDC pilot cutover from unscoped inventory data to organizationally scoped assets, locations, stock, attachments, maps, and audit history. Do not enable fail-closed enforcement until every preflight gate passes against a sanitized copy of the target database.

## Safety rules

- Never run PHPUnit against the local or production application database. The test environment must use an isolated database created only for that test run.
- Keep the legacy source connection read-only at both the application configuration and database-account levels.
- Take and verify a restorable database backup before applying migrations or mappings.
- Record the application commit, Composer lock hash, migration list, operator, start time, and backup location in the change ticket.
- Stop the cutover if active assets, users, or locations remain unmapped, hierarchy paths are invalid, or multiple open placements exist.

## 1. Preflight

1. Put the application in a scheduled maintenance window and stop queue workers and schedulers.
2. Confirm the deployed runtime and locked dependencies:

   ```powershell
   php --version
   php artisan --version
   composer validate
   composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   ```

3. Confirm the target and legacy connections point to different databases and the legacy credential cannot execute `INSERT`, `UPDATE`, `DELETE`, `ALTER`, `DROP`, or `TRUNCATE`.
4. Back up the target database using the approved database-administration procedure. Restore that backup into a temporary database and run a basic row-count comparison before proceeding.
5. Run the complete test suite against an isolated test database. Classify and resolve every failure relevant to hierarchy, authorization, placement, stock, maps, attachments, exports, or imports.

## 2. Sanitized rehearsal

1. Restore a sanitized copy of the target database into the rehearsal environment.
2. Apply migrations:

   ```powershell
   php artisan migrate --force
   ```

3. Seed the stable EPFO/NDC hierarchy.
4. Run the NDC mapper in dry-run and verification modes:

   ```powershell
   php artisan epfo:map-ndc-inventory --dry-run --verify
   ```

5. Review unmapped assets, locations, users, invalid paths, duplicate memberships, open-placement conflicts, and stock differences. Resolve every active-record discrepancy.
6. Apply the mapping, then repeat verification. Re-running apply and verify must be idempotent.
7. Exercise the second-office acceptance workflow to prove that no NDC-specific code changes are required.

## 3. Production cutover

1. Confirm the verified backup and rollback owner.
2. Enable maintenance mode and stop workers:

   ```powershell
   php artisan down
   ```

3. Apply migrations, seed stable hierarchy records, and run the mapper in apply mode.
4. Run the organizational-scoping verifier. The command must report zero unmapped active assets, users, and locations and no integrity conflicts.
5. Start queue workers and schedulers, clear application caches, and disable maintenance mode only after all gates pass.
6. Perform role-based smoke tests for Administrator, Inventory Manager, Stock Operator, Finance Operator, Viewer, and Auditor.

## 4. Acceptance checks

- NDC users cannot read sibling-office records without an authorized ancestor scope.
- Local write scope cannot mutate descendants; explicit descendant write scope can.
- Viewer and Auditor roles cannot mutate inventory even when memberships contain write scope.
- Direct unauthorized URLs return the documented response consistently without exposing record metadata.
- Asset moves create one placement-history transition and preserve assignment history.
- Stock transfers debit and credit the correct location balances exactly once.
- Private maps and attachments never expose storage paths or restricted coordinates.
- Dashboard counts, searches, exports, audit history, console commands, jobs, APIs, and importers use explicit organizational context.

## 5. Monitoring

For the pilot period, monitor and record:

- authorization denials grouped by route, role, and organizational unit;
- active records missing ownership or site mapping;
- invalid or unexpectedly deep hierarchy paths;
- slow hierarchy, visibility, placement, and stock queries;
- multiple-open-placement attempts and idempotency conflicts;
- stock reconciliation differences;
- failed private-map or attachment authorization attempts.

Do not add another office until the NDC pilot owner signs off on these metrics.

## 6. Rollback

1. Re-enable maintenance mode and stop workers immediately.
2. Preserve application and database logs for incident review.
3. If no post-cutover business transactions must be retained, restore the verified pre-cutover backup and deploy the previous application release.
4. If post-cutover transactions must be retained, do not run destructive migration rollbacks. Escalate to the database and application owners for a forward-repair or controlled data-export plan.
5. Verify row counts and critical inventory balances after restoration, clear caches, restart workers, and document the rollback outcome.
