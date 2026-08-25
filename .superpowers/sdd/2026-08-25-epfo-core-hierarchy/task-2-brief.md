# Task 2 Brief: Transactional Organizational Hierarchy Service

**Source Plan:** docs/superpowers/plans/2026-08-25-epfo-core-hierarchy.md

## Task 2: Transactional organizational hierarchy service
You are implementing the service that manages the Materialized Path for the `OrganizationalUnit` model. Since we deliberately excluded auto-path generation from the model events in Task 1, all creates and moves MUST go through this service.

### Files to Create:
1. `app/Exceptions/InvalidHierarchyMove.php` (Extend `Exception`).
2. `app/Services/Organization/OrganizationalHierarchyService.php`
3. `tests/Feature/Organization/OrganizationalHierarchyServiceTest.php`

### Steps & Requirements:
1. **Creation:** The service must provide a method to create a unit. If a `parent_id` is provided, it must calculate the correct `path` (e.g. `/{parent_id}/{id}/`). If no parent is provided, the path is `/{id}/`. 
2. **Move Subtree:** The service must provide a method to move an existing unit to a new parent. 
   - It MUST rewrite the `path` of the moved unit AND every descendant unit.
   - It MUST reject self-parenting (moving a unit to itself).
   - It MUST reject descendant-parent cycles (moving a unit to one of its own descendants). Throw `InvalidHierarchyMove`.
   - It MUST reject moving to an inactive or deleted parent. Throw `InvalidHierarchyMove`.
3. **Database Transactions:** The move operation must happen inside a database transaction (`DB::transaction`). If any descendant fails to update, the whole move must roll back.
4. **Descendant Discovery:** Ensure you use delimiter-safe prefixes when finding descendants (e.g., `LIKE '/1/2/%'`).
5. **Testing:** Write the feature test verifying all these scenarios (root creation, child creation, moving a subtree, cycle rejection, rollback on failure, and inactive parent rules).
6. Run the tests.
7. Commit the changes with: `feat: implement transactional organizational hierarchy service`.

Write your execution report to `.superpowers/sdd/2026-08-25-epfo-core-hierarchy/task-2-report.md`.
