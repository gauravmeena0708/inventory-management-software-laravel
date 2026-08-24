# Task 2 Brief: Authentication, Roles & Authorization Policies

## Objective
Implement role-based authorization for the 6 domain roles defined in `UserRole` (`admin`, `inventory_manager`, `stock_operator`, `finance_operator`, `viewer`, `auditor`), update the `User` model, create/update the users migration with `role`, implement domain policies (`AssetPolicy`, `ConsumablePolicy`, `AgreementPolicy`, `PaymentPolicy`, `OfficialPolicy`, `DeveloperPolicy`, `TaskPolicy`), replace legacy `Auth::routes()` in `routes/web.php` with clean standard auth/guest routes or Breeze/Fortify routing, and write tests for policy authorization.

## Permission Matrix (From Spec Section 7.1)
| Capability | Administrator | Inventory Manager | Stock Operator | Finance Operator | Viewer | Auditor |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| Manage users/roles | Yes | No | No | No | No | No |
| Create/update/decommission assets | Yes | Yes | No | No | No | No |
| Assign and return assets | Yes | Yes | No | No | No | No |
| Purchase/issue stock | Yes | Yes | Yes | No | No | No |
| Manage agreements | Yes | Yes | No | Yes | No | No |
| Complete/cancel payments | Yes | No | No | Yes | No | No |
| View ordinary inventory | Yes | Yes | Yes | Yes | Yes | Yes |
| Export data | Yes | Yes | Scoped | Scoped | No | Read-only audit |
| View sensitive personnel fields | Yes | Explicit | No | No | No | Explicit |
| View audit history | Yes | Scoped | Own | Scoped | No | Yes |

## Specific Requirements
1. **`app/Models/User.php`**:
   - Add `role` to `$fillable`.
   - Cast `role` => `UserRole::class`.
   - Implement role helpers: `hasRole(UserRole $role): bool`, `isAdmin(): bool`, `canManageInventory(): bool`, etc.
   - Update Spatie ActivityLog to v4/v5 `getActivitylogOptions()`:
     ```php
     public function getActivitylogOptions(): LogOptions
     {
         return LogOptions::defaults()->logFillable()->logOnlyDirty();
     }
     ```
2. **Database Migration for Users**:
   - Add `role` string column (`default('viewer')`) to `users` table or in new anonymous migration `database/migrations/2026_08_24_000001_create_users_and_roles_tables.php`.
3. **Policies in `app/Policies/`**:
   - `AssetPolicy.php`: `viewAny`, `view` (All authenticated roles); `create`, `update`, `assign`, `return`, `decommission` (`admin`, `inventory_manager`); `export` (`admin`, `inventory_manager`).
   - `ConsumablePolicy.php`: `viewAny`, `view` (All); `create`, `update` (`admin`, `inventory_manager`); `postEntry` / purchase/issue (`admin`, `inventory_manager`, `stock_operator`).
   - `AgreementPolicy.php`: `viewAny`, `view` (All); `create`, `update`, `delete`, `export` (`admin`, `inventory_manager`, `finance_operator`).
   - `PaymentPolicy.php`: `viewAny`, `view` (All); `complete`, `cancel` (`admin`, `finance_operator`).
4. **`routes/web.php`**:
   - Remove legacy `Auth::routes()`.
   - Add basic auth routes (login, logout) or Breeze standard routes without crashing Laravel 11/13 boot.
5. **`tests/Feature/AuthorizationPolicyTest.php`**:
   - Unit/Feature tests validating the matrix permissions for each role.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-2-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
