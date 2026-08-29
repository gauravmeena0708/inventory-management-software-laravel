# Task 4 Brief: Sites and Physical-Space Hierarchy

**Source Plan:** docs/superpowers/plans/2026-08-25-epfo-core-hierarchy.md

## Task 4: Sites and physical-space hierarchy
You are separating the concept of a physical space (Building, Room, Rack) from the organizational ownership by introducing `Site` models and expanding the existing `Location` model to be fully hierarchical.

### Files to Create / Modify:
1. `database/migrations/2026_08_25_000012_create_sites_and_expand_locations.php`
2. `app/Enums/LocationType.php`
3. `app/Models/Site.php`
4. `app/Models/Location.php`
5. `app/Services/Spatial/PhysicalHierarchyService.php`
6. `tests/Feature/Spatial/PhysicalHierarchyTest.php`

### Steps & Requirements:

**1. Migrations:**
- Create `sites` table: `id`, `code` (string, unique), `name` (string), `address` (text, nullable), `latitude` (decimal 10,7, nullable), `longitude` (decimal 10,7, nullable), `altitude` (decimal, nullable), `geofence_geojson` (json, nullable), `timezone` (string, nullable), `is_active` (boolean, default true), `timestamps`, `softDeletes`.
- Create `organizational_unit_site` pivot table: `organizational_unit_id` (FK), `site_id` (FK).
- Safely expand the existing `locations` table. **Do NOT drop existing legacy fields (like `name`, `type`, etc.)**. Add: `site_id` (nullable FK to sites), `parent_id` (nullable FK to locations), `code` (string, nullable), `location_type` (string, default 'OTHER'), `path` (string, indexed, nullable), `level_number` (string, nullable), `geometry_geojson` (json, nullable), `local_x` (decimal, nullable), `local_y` (decimal, nullable), `local_z` (decimal, nullable), `is_restricted` (boolean, default false), `is_active` (boolean, default true).

**2. Enums and Models:**
- Create `app/Enums/LocationType.php` with cases: `BUILDING`, `FLOOR`, `ZONE`, `ROOM`, `DATA_HALL`, `ROW`, `RACK`, `WORKSTATION`, `SEAT`, `STORE`, `BIN`, `NETWORK_POINT`, `OTHER`.
- Create `Site.php`: relations for `locations()` and `organizationalUnits()`. Cast boolean fields.
- Update `Location.php`: Add `site()`, `parent()`, and `children()` relations. Cast `location_type` to `LocationType`, `is_restricted` to bool, `is_active` to bool. Do NOT add auto-path generation to the model events.

**3. Physical Hierarchy Service:**
- Implement `app/Services/Spatial/PhysicalHierarchyService.php` (similar to the Organizational Hierarchy Service from Task 2).
- It must handle creating locations and moving location subtrees in a DB transaction while preventing cycles (a location becoming a child of its own descendant) or self-parenting. It must calculate the `path` correctly (e.g., `/{parent_id}/{id}/`).

**4. Testing:**
- Write `tests/Feature/Spatial/PhysicalHierarchyTest.php`.
- Test Site creation.
- Test expanding Location schema.
- Test the PhysicalHierarchyService (creation, moving subtree, cycle prevention, path generation).

**5. Verification & Commit:**
- Run the test suite and ensure tests pass.
- Commit changes with message: `feat: implement sites and physical space hierarchy`

Write your execution report to `.superpowers/sdd/2026-08-25-epfo-core-hierarchy/task-4-report.md`.
