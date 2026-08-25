# Task 3 Brief: Organizational Memberships and Default Context

**Source Plan:** docs/superpowers/plans/2026-08-25-epfo-core-hierarchy.md

## Task 3: Organizational memberships and default context

You are implementing the many-to-many relationship mapping users to their organizational units, including specific scopes for read/write behavior and the context switcher logic.

### Files to Create / Modify:
1. `database/migrations/2026_08_25_000011_create_organizational_memberships.php`
2. `app/Models/User.php`
3. `app/Models/OrganizationalUnit.php`
4. `app/Services/Organization/OrganizationalContext.php`
5. `tests/Feature/Organization/OrganizationalMembershipTest.php`

### Steps & Requirements:

**1. Migrations:**
Create the `organizational_unit_user` table with:
- `organizational_unit_id` (FK)
- `user_id` (FK)
- `read_scope` (string, default 'local', allowed: 'local', 'descendants')
- `write_scope` (string, default 'none', allowed: 'none', 'local', 'descendants')
- `valid_from` (date or datetime, nullable)
- `valid_until` (date or datetime, nullable)
- `timestamps`
- Unique constraint on `(organizational_unit_id, user_id)`

In the same migration, safely add `default_organizational_unit_id` to the `users` table as a nullable FK pointing to `organizational_units.id`.

**2. Models:**
- `User.php`: Add `organizationalUnits()` belongsToMany relation (withPivot for all extra columns, and withTimestamps). Add a helper `activeOrganizationalUnits()` which returns units where today's date falls between `valid_from` and `valid_until` (if set). Add `defaultOrganizationalUnit()` belongsTo relation.
- `OrganizationalUnit.php`: Add `users()` belongsToMany relation.

**3. OrganizationalContext Service:**
- Implement `app/Services/Organization/OrganizationalContext.php`.
- Provide a method to validate and set the default unit for a user. It MUST validate that the unit belongs to one of their *active* memberships.
- Provide methods to get/set the active UI context (typically stored in session, or return default if no session). It must reject unauthorized (non-member) context values.

**4. Testing:**
- Write `tests/Feature/Organization/OrganizationalMembershipTest.php`.
- Test users with no membership, multiple memberships, expired memberships.
- Test setting authorized vs unauthorized default contexts.
- Test overlapping ancestor/descendant memberships if applicable, but at least test basic validation logic.

**5. Verification & Commit:**
- Run the test suite and ensure tests pass.
- Commit changes with message: `feat: implement organizational memberships and user context`

Write your execution report to `.superpowers/sdd/2026-08-25-epfo-core-hierarchy/task-3-report.md`.
