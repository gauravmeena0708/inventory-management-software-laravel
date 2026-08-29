# Task 2 Execution Report: Transactional Organizational Hierarchy Service

## What Was Done
1. **Created Exception:** `app/Exceptions/InvalidHierarchyMove.php` extends `Exception` and serves as a distinct error type when hierarchy invariants are violated during movement.
2. **Created Hierarchy Service:** `app/Services/Organization/OrganizationalHierarchyService.php` contains the logic for creating and moving units.
    - **Creation:** Calculates the Materialized Path and depth upon unit creation via `createUnit`. It validates active parents, parent/child unit types, and the single EPFO root invariant.
    - **Move Subtree:** `moveUnit` safely updates the node and all descendants, including both materialized paths and depth values.
    - **Validation:** Creation and movement reject self/descendant cycles, inactive or deleted parents, invalid type relationships, missing paths, and additional roots.
    - **Transactions and locking:** Creation and movement run inside database transactions and lock the affected unit, parent, and descendant rows before rewriting the hierarchy.
3. **Created Feature Tests:** `tests/Feature/Organization/OrganizationalHierarchyServiceTest.php` provides thorough test coverage for creating root nodes, child nodes, moving subtrees, path cascading, rejection of cycle-inducing moves, failure to move into inactive/deleted components, and transaction rollback verification.

## Challenges & Solutions
- The initial test failed because the `unit_type` column expects specific `OrganizationalUnitType` enums instead of arbitrary strings like `'company'`. I updated the tests to correctly reference the valid enum configurations (e.g. `OrganizationalUnitType::ROOT->value`, `OrganizationalUnitType::HEAD_OFFICE->value`).
- When asserting validation constraints on the parent nodes being active during a move, the tests required `is_active => true` explicitly configured. I added it to the factory definitions to align with Laravel model properties casting without default boolean configurations on initial build.
- Database-agnostic prefix rewriting is used because string functions differ across database drivers. The organizational tree is expected to remain relatively small; descendants are fetched under row locks and updated within the same transaction.

## Tests Run
- Command: `php artisan test tests/Feature/Organization/OrganizationalHierarchyServiceTest.php`
- Current outcome: 12 hierarchy-service tests pass as part of the combined Task 1-3 verification suite.

All required behaviors successfully verified and committed under `feat: implement transactional organizational hierarchy service`.
