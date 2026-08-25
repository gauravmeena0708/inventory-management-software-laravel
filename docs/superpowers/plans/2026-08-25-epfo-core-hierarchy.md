# EPFO NDC-First Organizational, Spatial, and Access-Control Plan

## 1. Goal

Build the organizational and spatial foundation needed to pilot the inventory system at NDC while ensuring that another EPFO office can be added later through configuration and data, without changing the database architecture or authorization model.

The implementation must keep these concepts separate:

1. **Organizational authority** — which EPFO unit owns a record and which users may see or change it.
2. **Geographical site** — the address, GPS position, and boundary of an office or campus.
3. **Indoor space** — building, floor, room, data hall, rack, workstation, seat, store, or bin.
4. **Inventory placement** — the current and historical physical position of an asset or stock balance.
5. **Map representation** — versioned 2D floor plans now and optional 3D models later.

NDC is the first configured organizational unit and site, not a hard-coded application special case.

## 2. Current Repository Baseline

- Laravel 13.x.
- PHP 8.4 or later.
- PHPUnit 12.
- MySQL-compatible production database.
- Existing global roles in `App\Enums\UserRole` remain the capability layer.
- Existing `locations` records contain physical information such as building, floor, seat, point, and PIN.
- Existing assets reference `locations.id` through `assets.location_id`.
- Existing users do not have a `location_id` column in the consolidated schema.
- Consumables currently keep one global `in_stock` value and require a per-location stock redesign before organizational scoping can be enforced.
- The legacy importer connection and its source database remain read-only and must not be broken.

## 3. Architectural Decisions

### 3.1 Organizational hierarchy

Create a new `organizational_units` table. Do not turn physical `locations` into EPFO offices.

Use one synthetic `EPFO` root with branch and office nodes beneath it. Initial data includes NDC. Supported unit types must be data-independent PHP enum values, including:

- `ROOT`
- `HEAD_OFFICE`
- `NDC`
- `ZONAL_OFFICE`
- `REGIONAL_OFFICE`
- `DISTRICT_OFFICE`
- `SPECIAL_STATE_OFFICE`
- `VIGILANCE_HQ`
- `VIGILANCE_ZVD`
- `PDUNASS`
- `ZTI`

Use a materialized path for efficient descendant reads. All create and move operations go through an `OrganizationalHierarchyService`; do not manage subtree paths solely through model events.

### 3.2 User membership and authorization

Users belong to one or more organizational units through `organizational_unit_user`.

Authorization is always:

```text
global role capability
AND organizational scope
AND record-state/business rules
```

Membership grants data scope, but never grants a capability that the user's global role does not have. For example, a Viewer assigned to NDC remains read-only.

Default behavior is Read-Down and Write-Local. Exceptional responsibilities are represented as membership scope fields rather than hard-coded office names.

### 3.3 Physical hierarchy

Keep the `locations` table for physical spaces and preserve `assets.location_id` for compatibility. Add `sites` above locations and make locations hierarchical.

```text
Site
└── Building
    └── Floor
        └── Zone / Room / Data Hall
            └── Row / Rack / Workstation / Seat / Store / Bin
```

GPS belongs to a site or building-level location. Indoor positions use local X/Y/Z coordinates relative to a calibrated map origin.

### 3.4 Maps and 3D readiness

Maps are a visualization of structured spatial data, not the source of truth. Building, floor, room, rack, workstation, and seat remain searchable database records.

The first release supports private, versioned 2D floor plans and interactive markers. The schema reserves `3d_model` map types and local Z coordinates, but a 3D viewer is not part of this core implementation.

### 3.5 Application data scoping

Do not describe an Eloquent global scope as database RLS. Implement reusable `visibleTo(User $user)` query scopes/services plus model policies. A global scope may be added later as defense in depth only after CLI, jobs, imports, exports, dashboards, and route binding are tested.

### 3.6 Safe migration

- Do not drop a presumed `users.location_id` column.
- If an older deployed schema contains that column, handle it conditionally in a dedicated compatibility migration or command.
- Do not run `php artisan migrate:fresh` against a developer or production database as a plan step.
- Use the isolated test database through PHPUnit and `RefreshDatabase`.
- Backfill and reconcile data before making new ownership fields non-null or enabling fail-closed scope enforcement.

## 4. Target Data Model

### 4.1 `organizational_units`

```text
id
parent_id nullable FK organizational_units.id
code unique
name
unit_type
path indexed nullable during backfill
depth unsigned integer
is_active boolean
metadata JSON nullable
timestamps
soft_deletes
```

Rules:

- `code` is a stable business identifier; paths may use database IDs internally.
- A unit cannot be its own parent or a descendant of itself.
- Moving a unit updates every descendant path atomically.
- Inactive or soft-deleted units cannot receive new inventory.
- A unit with active descendants or owned records cannot be deleted without an explicit reassignment workflow.

### 4.2 `organizational_unit_user`

```text
id
organizational_unit_id FK
user_id FK
read_scope: local | descendants
write_scope: none | local | descendants
valid_from nullable
valid_until nullable
timestamps
unique (organizational_unit_id, user_id)
```

Add `users.default_organizational_unit_id` as a nullable FK. Validate that the selected default is one of the user's active memberships. Store the active UI context in the session; changing context must not silently rewrite the default.

### 4.3 `sites`

```text
id
code unique
name
address fields
latitude decimal(10,7) nullable
longitude decimal(10,7) nullable
altitude decimal nullable
geofence_geojson JSON nullable
timezone
is_active boolean
timestamps
soft_deletes
```

Use `organizational_unit_site` to associate one or more units with a site without assuming a permanent one-to-one relationship.

### 4.4 Updated `locations`

Add without removing legacy-compatible columns:

```text
site_id nullable FK sites.id
parent_id nullable FK locations.id
code nullable
location_type
path indexed nullable during backfill
level_number nullable
geometry_geojson JSON nullable
local_x decimal nullable
local_y decimal nullable
local_z decimal nullable
is_restricted boolean
is_active boolean
```

Supported initial physical types:

- `BUILDING`
- `FLOOR`
- `ZONE`
- `ROOM`
- `DATA_HALL`
- `ROW`
- `RACK`
- `WORKSTATION`
- `SEAT`
- `STORE`
- `BIN`
- `NETWORK_POINT`
- `OTHER`

Keep existing `building`, `floor`, `seat`, `point`, `sublocation`, and `pin` data until migration reconciliation confirms that structured spaces fully represent it.

### 4.5 Asset ownership and placement

Add nullable `organizational_unit_id` to `assets` during transition. Keep `assets.location_id` as the denormalized current physical location.

Create `asset_placements`:

```text
id
asset_id FK
location_id FK
position_x nullable
position_y nullable
position_z nullable
rack_start_unit nullable
rack_unit_height nullable
placed_at
removed_at nullable
placed_by nullable FK users.id
remarks nullable
timestamps
```

An asset-placement service must update `assets.location_id`, close the previous placement, create the new placement, and record activity in one transaction. Enforce at most one open placement per asset through service validation and database-supported constraints where practical.

### 4.6 Spatial maps

Create `spatial_maps`:

```text
id
location_id FK
attachment_id FK
map_type: image | svg | geojson | 3d_model
version
width nullable
height nullable
scale nullable
origin_x nullable
origin_y nullable
origin_z nullable
rotation nullable
coordinate_system nullable
calibration JSON nullable
is_current boolean
uploaded_by nullable FK users.id
timestamps
soft_deletes
```

Map files use the existing private attachment storage. Access uses signed or authorized application routes. Do not expose private storage paths.

### 4.7 Per-location consumable stock

Treat `consumables` as the catalog and introduce:

```text
stock_balances
- consumable_id
- location_id
- quantity
- min_quantity
- max_quantity
- unique (consumable_id, location_id)

stock_transactions
- consumable_id
- source_location_id nullable
- destination_location_id nullable
- transaction_type
- quantity
- source_stock_after nullable
- destination_stock_after nullable
- recipient_official_id nullable
- recorded_by nullable
- idempotency_key unique
- remarks nullable
- created_at
```

Purchases, issues, adjustments, and transfers must use transactions and row locks. Preserve the existing ledger during migration and reconcile its final stock into the NDC store balance.

## 5. Authorization Rules

### 5.1 Role capability remains authoritative

- Administrator: explicit organization-wide bypass, with audited mutations.
- Inventory Manager: inventory mutations within granted write scope.
- Stock Operator: stock postings within granted write scope; no asset-master mutations.
- Finance Operator: financial capabilities only within future financial ownership scope.
- Auditor: read within granted scope, including permitted sensitive history; no mutations.
- Viewer: ordinary read within granted scope; no mutations.

Use `UserRole` enum cases, never unverified strings such as `'ADMIN'`.

### 5.2 Visibility

`Read-Down` means a user can read records owned by an assigned organizational unit and, when membership `read_scope` is `descendants`, its descendants.

Breadcrumbs may expose minimal ancestor names needed for navigation, but must not expose sibling inventory.

### 5.3 Mutation

`Write-Local` means a capable user can mutate records owned by the exact organizational unit in an active membership whose `write_scope` permits local writes. Descendant writes require an explicit `descendants` grant.

Moving an asset must verify write access to both source and destination ownership/location contexts.

### 5.4 Sensitive maps

Introduce policy abilities such as:

```text
site.view
spatial-map.view
spatial-map.manage
asset-placement.view
asset-placement.update
restricted-space.view
rack-layout.view
```

Ordinary asset visibility must not automatically reveal exact GPS coordinates, restricted rooms, rack positions, IP addresses, or detailed NDC floor plans.

## 6. Implementation Tasks

### Task 0: Preflight and contract tests

**Create or update:**

- `tests/Feature/Architecture/CurrentSchemaContractTest.php`
- `tests/Feature/Architecture/LegacyImporterContractTest.php`
- `docs/superpowers/specs/2026-08-25-epfo-core-hierarchy-design.md`

**Steps:**

- [ ] Confirm Laravel/PHP/PHPUnit versions from `composer.json` and runtime.
- [ ] Record the current user, location, asset, consumable, and ledger schema in contract tests.
- [ ] Assert the current schema does not require `users.location_id`.
- [ ] Assert legacy source configuration remains read-only.
- [ ] Update the companion design specification to match this plan.
- [ ] Run the existing test suite and record pre-existing failures separately from hierarchy work.

### Task 1: Organizational-unit schema

**Create:**

- `app/Enums/OrganizationalUnitType.php`
- `app/Models/OrganizationalUnit.php`
- `database/factories/OrganizationalUnitFactory.php`
- `database/migrations/2026_08_25_000010_create_organizational_units_table.php`
- `tests/Feature/Organization/OrganizationalUnitSchemaTest.php`

**Steps:**

- [ ] Write tests for columns, foreign keys, indexes, enum casts, and unique code.
- [ ] Create the nullable-path transitional schema.
- [ ] Add parent/children relationships and non-auth-dependent query helpers.
- [ ] Do not add automatic subtree rewriting to model events.
- [ ] Verify migration rollback in the test database.

### Task 2: Transactional organizational hierarchy service

**Create:**

- `app/Services/Organization/OrganizationalHierarchyService.php`
- `app/Exceptions/InvalidHierarchyMove.php`
- `tests/Feature/Organization/OrganizationalHierarchyServiceTest.php`

**Steps:**

- [ ] Test root and child path creation.
- [ ] Test descendant discovery with delimiter-safe prefixes.
- [ ] Test moving a subtree and rewriting every descendant path.
- [ ] Test rejection of self-parenting and descendant-parent cycles.
- [ ] Test rollback when a subtree update fails.
- [ ] Test inactive/deleted parent rules.
- [ ] Implement create/move operations using database transactions and appropriate locks.

### Task 3: Organizational memberships and default context

**Create or update:**

- `database/migrations/2026_08_25_000011_create_organizational_memberships.php`
- `app/Models/User.php`
- `app/Models/OrganizationalUnit.php`
- `app/Services/Organization/OrganizationalContext.php`
- `tests/Feature/Organization/OrganizationalMembershipTest.php`

**Steps:**

- [ ] Create the membership pivot with a composite unique constraint and validity dates.
- [ ] Add `users.default_organizational_unit_id` safely.
- [ ] Add membership relationships and active-membership helpers.
- [ ] Validate that the default belongs to an active membership.
- [ ] Keep the active context in session and reject unauthorized context values.
- [ ] Test users with no membership, multiple memberships, expired membership, and overlapping ancestor/descendant membership.

### Task 4: Sites and physical-space hierarchy

**Create or update:**

- `app/Enums/LocationType.php`
- `app/Models/Site.php`
- `app/Models/Location.php`
- `database/migrations/2026_08_25_000012_create_sites_and_expand_locations.php`
- `tests/Feature/Spatial/PhysicalHierarchyTest.php`

**Steps:**

- [ ] Create sites, organizational-unit/site associations, GPS fields, and geofence metadata.
- [ ] Add physical hierarchy and geometry fields to locations without dropping legacy fields.
- [ ] Implement a transactional physical-hierarchy service with cycle and subtree-move tests.
- [ ] Validate latitude, longitude, local coordinates, and location-type parent rules.
- [ ] Define whether a site's root locations may be shared by several units.
- [ ] Preserve current location CRUD behavior during transition.

### Task 5: NDC seed and existing-data mapping

**Create:**

- `database/seeders/EpfoNdcHierarchySeeder.php`
- `app/Console/Commands/MapExistingInventoryToNdc.php`
- `app/Services/Organization/NdcMigrationReconciler.php`
- `tests/Feature/Organization/NdcBackfillTest.php`

**Steps:**

- [ ] Seed EPFO root, NDC unit, and NDC site idempotently using stable codes.
- [ ] Map existing physical locations under the NDC site without destroying original values.
- [ ] Add nullable `organizational_unit_id` to assets and backfill it to NDC.
- [ ] Assign existing users through an explicit role-to-membership mapping; do not give every user write access.
- [ ] Support dry-run, apply, resume, and verification modes.
- [ ] Reconcile unmapped assets, locations, users, and invalid paths before enforcement.
- [ ] Keep the legacy source database read-only.

### Task 6: Asset ownership, placement, and relocation

**Create or update:**

- `app/Models/AssetPlacement.php`
- `app/Services/Assets/AssetPlacementService.php`
- `database/migrations/2026_08_25_000013_create_asset_placements_table.php`
- `app/Policies/AssetPolicy.php`
- `tests/Feature/Assets/AssetPlacementTest.php`
- `tests/Feature/Assets/ScopedAssetAuthorizationTest.php`

**Steps:**

- [ ] Backfill one current placement for assets with a location.
- [ ] Move assets through a transaction that closes the old placement and updates `assets.location_id`.
- [ ] Validate source and destination permissions.
- [ ] Preserve assignment history separately from physical placement history.
- [ ] Test concurrent moves and prevent multiple open placements.
- [ ] Combine current role checks with organizational write scope.
- [ ] Confirm Viewer and Auditor cannot mutate assets even when locally assigned.

### Task 7: Reusable visibility queries and policy coverage

**Create or update:**

- `app/Services/Authorization/OrganizationalVisibility.php`
- model scopes such as `scopeVisibleTo()` on owned models
- relevant policies, controllers, exports, and dashboard queries
- `tests/Feature/Authorization/OrganizationalVisibilityTest.php`
- `tests/Feature/Authorization/DataLeakageTest.php`

**Steps:**

- [ ] Implement local and descendant organizational visibility without depending on ambient `Auth` state.
- [ ] Deduplicate results for overlapping memberships.
- [ ] Define explicit administrator behavior.
- [ ] Test self, child, parent, sibling, unrelated, inactive, and missing-ownership records.
- [ ] Apply visibility to asset lists, detail routes, dashboard counts, searches, exports, attachments, and audit views.
- [ ] Establish ownership fields or inherited ownership rules before scoping officials, files, agreements, payments, tasks, or future issues.
- [ ] Verify console commands, queue jobs, API requests, and legacy imports use explicit service identities or system context.
- [ ] Decide and test whether unauthorized direct URLs return 403 or 404 consistently.

### Task 8: Per-location consumable stock

**Create or update:**

- `app/Models/StockBalance.php`
- `app/Models/StockTransaction.php`
- `app/Services/Stock/LocationStockService.php`
- migrations for stock balances and transactions
- stock controllers, policies, and views
- `tests/Feature/Stock/LocationStockTest.php`
- `tests/Feature/Stock/InterOfficeTransferTest.php`

**Steps:**

- [ ] Treat consumables as a shared catalog and quantities as location-specific balances.
- [ ] Migrate current stock to a designated NDC store location.
- [ ] Preserve and reconcile the existing immutable ledger.
- [ ] Implement purchase, issue, adjustment, and transfer with row locks and idempotency.
- [ ] Require write access to the source and authorized acceptance at the destination.
- [ ] Test insufficient stock, replayed requests, concurrent issue, and cross-office isolation.

### Task 9: Private spatial maps and 2D interaction foundation

**Create or update:**

- `app/Models/SpatialMap.php`
- `app/Policies/SpatialMapPolicy.php`
- `app/Http/Controllers/SpatialMapController.php`
- `database/migrations/2026_08_25_000014_create_spatial_maps_table.php`
- location/site views and routes
- `tests/Feature/Spatial/SpatialMapSecurityTest.php`

**Steps:**

- [ ] Create versioned spatial-map records linked to private attachments.
- [ ] Validate permitted file types, size, checksum, and map metadata.
- [ ] Implement authorized upload, view/download, supersede, and delete flows.
- [ ] Add calibration metadata so markers are not tied only to image pixels.
- [ ] Display hierarchical locations and asset markers on the current 2D map.
- [ ] Allow map relocation only through `AssetPlacementService` and policy checks.
- [ ] Reserve `3d_model`, Z coordinate, and coordinate-system fields without implementing a 3D viewer.
- [ ] Test that unauthorized users cannot infer private map paths or restricted coordinates.

### Task 10: NDC context and hierarchy UI

**Create or update:**

- authenticated layout navigation
- organizational-unit, site, and location controllers/forms/views
- context-switch endpoint and request validation
- `tests/Feature/Frontend/OrganizationalContextUiTest.php`

**Steps:**

- [ ] Add the organizational context switcher for users with multiple memberships.
- [ ] Add site/building/floor/room/rack tree navigation and breadcrumbs.
- [ ] Filter selectors to authorized organizational units and physical spaces.
- [ ] Keep structured list/search views available even when no map is uploaded.
- [ ] Hide unauthorized map and mutation controls while retaining server-side enforcement.
- [ ] Add accessible non-map alternatives for all essential operations.

### Task 11: Second-office expansion proof

**Create:**

- `database/seeders/EpfoExpansionDemoSeeder.php` for test/development only
- `tests/Feature/Organization/SecondOfficeExpansionTest.php`

**Steps:**

- [ ] Create a second organizational unit, site, building, floor, user, asset, and stock balance entirely through public services/data configuration.
- [ ] Confirm no NDC-specific code change is required.
- [ ] Test NDC versus second-office read isolation.
- [ ] Test authorized ancestor read-down.
- [ ] Test write-local denial and explicit descendant-write grant.
- [ ] Test asset and stock transfer workflows between the two offices.
- [ ] Confirm dashboards, exports, maps, attachments, and audit history respect scope.

### Task 12: Cutover and acceptance

**Create:**

- `app/Console/Commands/VerifyOrganizationalScoping.php`
- deployment/runbook documentation

**Steps:**

- [ ] Run all hierarchy, authorization, placement, stock, map, and importer tests.
- [ ] Run the complete existing PHPUnit suite and classify unrelated legacy failures.
- [ ] Run the NDC mapping command in dry-run mode against a sanitized copy of real data.
- [ ] Require zero unmapped active assets, users, and locations before fail-closed enforcement.
- [ ] Back up the target database and document rollback steps.
- [ ] Enable organizational enforcement for the NDC pilot.
- [ ] Monitor unauthorized-access errors, unmapped records, slow hierarchy queries, and stock reconciliation differences.
- [ ] Obtain pilot acceptance before loading additional offices.

## 7. Required Test Matrix

Every scoped inventory action must cover at least:

| Scenario | Read | Write |
|---|---:|---:|
| Exact active membership, capable role | Yes | Yes |
| Descendant, read-down and write-local | Yes | No |
| Descendant with explicit descendant write | Yes | Yes |
| Parent of assigned unit | Minimal breadcrumb only | No |
| Sibling unit | No | No |
| Unrelated unit | No | No |
| Expired membership | No | No |
| Viewer at local unit | Yes | No |
| Auditor at local unit | Yes | No |
| Administrator | Yes | Yes, audited |
| Missing organizational ownership after enforcement | No | No |

Also test:

- overlapping memberships;
- inactive and soft-deleted units/sites/spaces;
- hierarchy cycles and subtree movement;
- unauthorized route model binding;
- dashboard and export leakage;
- attachment and map-file access;
- concurrent asset relocation;
- concurrent stock issue and transfer;
- audit records for hierarchy, membership, placement, and map changes.

## 8. Performance and Indexing

- Index organizational and physical hierarchy paths.
- Index all ownership and current-location foreign keys.
- Index membership validity and user/unit pairs.
- Avoid loading all membership models to authorize each row; resolve accessible unit IDs or path predicates once per request/service operation.
- Use eager loading for hierarchy lists and map markers.
- Benchmark descendant queries and dashboard aggregates with representative multi-office data before rollout.
- Do not claim materialized paths provide massive scalability until tested with expected EPFO hierarchy and inventory volumes.

## 9. Definition of Done

The core hierarchy is complete only when:

- NDC can be configured without NDC-specific authorization code.
- An additional office can be added without schema or code changes.
- Organizational ownership and physical placement are separately represented.
- Existing location information and importer behavior are preserved and reconciled.
- Role permissions remain intact and are narrowed by organizational scope.
- Dashboard, exports, attachments, maps, and direct URLs do not leak cross-office data.
- Assets retain physical placement history.
- Consumable quantities are location-specific.
- NDC site GPS and calibrated 2D maps can be stored privately.
- The schema can reference a future 3D model without requiring a redesign.
- The required test matrix passes and cutover/rollback procedures are documented.

## 10. Explicit Non-Goals for This Core Plan

- Rendering a complete interactive 3D building model.
- Live IoT sensor ingestion or heatmaps.
- Network auto-discovery.
- Full MantisBT-style issue/work-order implementation.
- Organization-wide rollout before NDC pilot acceptance.

The data model must permit these future capabilities, but they should be implemented as separate plans after this foundation is proven at NDC.
