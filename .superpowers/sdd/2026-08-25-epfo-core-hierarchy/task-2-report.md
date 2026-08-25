# Task 2 Execution Report: Transactional Organizational Hierarchy Service

## What Was Done
1. **Created Exception:** `app/Exceptions/InvalidHierarchyMove.php` extends `Exception` and serves as a distinct error type when hierarchy invariants are violated during movement.
2. **Created Hierarchy Service:** `app/Services/Organization/OrganizationalHierarchyService.php` contains the logic for creating and moving units.
    - **Creation:** Calculates the Materialized Path upon unit creation via `createUnit`. Correctly inherits its parent's path if provided, or establishes a root path (`/{id}/`). Wraps the creation in a transaction.
    - **Move Subtree:** Implemented `moveUnit(OrganizationalUnit $unit, ?OrganizationalUnit $newParent)` which safely updates the node and all of its descendants, retaining tree structure. It performs a cycle-safe delimiter-aware descendant discovery.
    - **Validation:** Added validation in the move logic to throw an `InvalidHierarchyMove` exception if attempting to move a unit to itself, one of its own descendants, an inactive unit, or a deleted parent.
    - **Transactions:** The move operation strictly runs inside a `DB::transaction` block. All paths and children modifications are persisted iteratively, meaning any failure immediately rolls back the parent unit's move and prevents orphaned branches.
3. **Created Feature Tests:** `tests/Feature/Organization/OrganizationalHierarchyServiceTest.php` provides thorough test coverage for creating root nodes, child nodes, moving subtrees, path cascading, rejection of cycle-inducing moves, failure to move into inactive/deleted components, and transaction rollback verification.

## Challenges & Solutions
- The initial test failed because the `unit_type` column expects specific `OrganizationalUnitType` enums instead of arbitrary strings like `'company'`. I updated the tests to correctly reference the valid enum configurations (e.g. `OrganizationalUnitType::ROOT->value`, `OrganizationalUnitType::HEAD_OFFICE->value`).
- When asserting validation constraints on the parent nodes being active during a move, the tests required `is_active => true` explicitly configured. I added it to the factory definitions to align with Laravel model properties casting without default boolean configurations on initial build.
- Database agnostic prefixing update logic was required since string functions (`CONCAT`, `SUBSTR`) differ heavily across driver engines (MySQL/SQLite) and the test suites likely run SQLite. To guarantee safety and compliance with Eloquent behavior, descendants are safely fetched by path (`LIKE ...%`) and updated via substring extraction natively in PHP, which performs optimally with chunks for deeper nested systems, ensuring exact mapping across all relational DB platforms. 

## Tests Run
- Command: `php artisan test tests/Feature/Organization/OrganizationalHierarchyServiceTest.php`
- Outcome: `{"tool":"phpunit","result":"passed","tests":8,"passed":8,"assertions":22,"duration_ms":4403}`

All required behaviors successfully verified and committed under `feat: implement transactional organizational hierarchy service`.
