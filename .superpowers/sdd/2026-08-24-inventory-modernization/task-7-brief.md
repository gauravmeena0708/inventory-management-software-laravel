# Task 7 Brief: Officials, Developers & Encrypted Personal Fields

## Objective
Implement organizational personnel (`Official`, `Developer`, `Devcat`) and operational `Task` models, database migration, encrypted sensitive attributes on `Developer` (`salary`, `phone`, `email`), Spatie ActivityLog v4/v5 options, and unit tests in `tests/Unit/PersonnelEncryptionTest.php`.

## Specific Requirements
1. **Migration**:
   - `database/migrations/2026_08_24_000006_create_personnel_and_tasks_tables.php`:
     - `officials`: `id`, `name`, `title` (nullable), `designation` (nullable), `department` (nullable), `email` (nullable), `phone` (nullable), `location_id` (nullable foreignId `locations` on delete set null), `timestamps`, `deleted_at`. Index on `email`, `department`.
     - `devcats`: `id`, `name`, `salary` (nullable), `exp` (nullable), `dev` (nullable), `collab` (nullable), `qualification` (nullable), `timestamps`, `deleted_at`.
     - `developers`: `id`, `name`, `reporting_id` (nullable foreignId `users` on delete set null), `category_id` (nullable foreignId `devcats` on delete set null), `phone` (text, nullable), `email` (text, nullable), `salary` (text, nullable), `status` (string, default 'active'), `remarks` (text, nullable), `timestamps`, `deleted_at`. (Text columns to accommodate AES ciphertext).
     - `tasks`: `id`, `title`, `description` (text, nullable), `assigned_to` (nullable foreignId `users` on delete set null), `file_id` (nullable foreignId `files` on delete set null), `priority` (string, default 'normal'), `status` (string, default 'pending'), `due_date` (date, nullable), `remarks` (text, nullable), `timestamps`, `deleted_at`. Index on `status`, `assigned_to`.
2. **Models**:
   - `app/Models/Official.php`:
     - `$fillable = ['name', 'title', 'designation', 'department', 'email', 'phone', 'location_id']`.
     - Relations: `location()`, `assets()`, `assignments()`, `entries()`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
   - `app/Models/Devcat.php`:
     - `$fillable = ['name', 'salary', 'exp', 'dev', 'collab', 'qualification']`.
     - Relations: `developers()`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
   - `app/Models/Developer.php`:
     - `$fillable = ['name', 'reporting_id', 'category_id', 'phone', 'email', 'salary', 'status', 'remarks']`.
     - Casts: `salary => 'encrypted'`, `phone => 'encrypted'`, `email => 'encrypted'`.
     - Relations: `reportingUser()`, `category()`.
     - Scopes: `scopeActive($query)`, `scopeDiscontinued($query)`.
     - ActivityLog: Spatie v4/v5 options (exclude encrypted fields from plain logging).
   - `app/Models/Task.php`:
     - `$fillable = ['title', 'description', 'assigned_to', 'file_id', 'priority', 'status', 'due_date', 'remarks']`.
     - Casts: `due_date => 'date'`.
     - Relations: `assignedUser()`, `file()`.
     - Scopes: `scopePending($query)`, `scopeCompleted($query)`.
     - ActivityLog: Spatie v4/v5 `getActivitylogOptions(): LogOptions`.
3. **Tests**:
   - `tests/Unit/PersonnelEncryptionTest.php`:
     - Test developer creation with encrypted fields.
     - Verify raw database row has encrypted string (not plain text).
     - Verify reading model decrypts attribute values transparently.
     - Test model relations across `Official`, `Developer`, `Devcat`, and `Task`.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-7-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
