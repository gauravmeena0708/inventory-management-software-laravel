# EPFO Core Hierarchy Design

## Status and scope

This specification defines the organizational and physical hierarchy used by the EPFO inventory application. NDC is the first configured office, not a special case in application code. The volatile framework/runtime selection remains in the platform baseline; this document defines domain contracts that should survive framework upgrades.

The design covers organizational units, memberships, sites, indoor locations, asset ownership and placement, NDC cutover, authorization boundaries, location stock, and private maps. It does not authorize destructive changes to the legacy source database.

## Separation of concerns

The application keeps five independent concepts:

1. An `OrganizationalUnit` describes authority and record ownership.
2. A `Site` describes a campus or office address and geographic position.
3. A `Location` describes structured indoor space within a site.
4. An asset placement records physical position and history.
5. A spatial map visualizes locations; it is never their source of truth.

An organizational unit may use several sites, and a site may be used by several organizational units. An asset therefore has both an organizational owner and a physical location.

## Organizational hierarchy

`organizational_units` is a self-referencing tree with a stable unique `code`, an enum-backed `unit_type`, nullable transitional `path`, `depth`, active state, metadata, and soft deletion. The initial hierarchy contains the stable codes `EPFO` and `NDC`.

Paths use delimiter-safe database IDs (`/1/4/9/`). Creation and subtree moves go through `OrganizationalHierarchyService`, run in transactions, lock affected records, reject cycles and inactive/deleted parents, and update every descendant path. Model events do not rewrite paths.

The generic `DIRECTORATE`, `DIVISION`, `BRANCH`, and `SECTION` types allow new office structures without schema changes.

## Membership and context

Users receive scope through `organizational_unit_user`, not `users.location_id`. A membership defines independent read and write scopes plus validity dates. A user's default unit must be one of that user's active memberships. The current UI context is session state and does not silently rewrite that default.

Authorization is the intersection of:

```text
global role capability
AND active organizational membership scope
AND record-state/business rules
```

The default is read-down and write-local. Administrator bypass is explicit and audited. Membership never grants a capability absent from the global role.

## Sites and locations

`sites` stores a unique code, address, optional WGS84 latitude/longitude, altitude, optional GeoJSON geofence, timezone, active state, and soft deletion. Latitude is limited to -90 through 90 and longitude to -180 through 180.

`locations` retains every legacy field (`name`, `sublocation`, `building`, `floor`, `seat`, `point`, `pin`, and description) while adding site, parent, code, enum type, path, geometry, local X/Y/Z, restricted state, and active state. This permits legacy CRUD and reconciliation to continue during structured-space adoption.

Structured parent rules are:

- A building is a site root.
- A floor belongs to a building.
- A zone belongs to a building or floor.
- A room belongs to a floor or zone.
- A data hall belongs to a floor, zone, or room.
- A row belongs to a zone, room, or data hall.
- A rack belongs to a zone, room, data hall, or row.
- A workstation belongs to a floor, zone, or room.
- A seat belongs to a zone, room, or workstation.
- A store belongs to a building, floor, zone, or room.
- A bin belongs to a store.
- A network point may belong to any structured container that can physically host it.
- `OTHER` remains permissive while legacy locations are classified.

Children inherit their parent's site. Moving a subtree updates its paths and site consistently. Local X/Y/Z values are finite decimals relative to a future map calibration origin.

### Shared site-root decision

Root locations belong to a site, not directly to an organizational unit. Therefore all organizational units associated with a site share the site's building roots and physical tree. Organizational authorization is still applied through the site's unit associations; sharing a physical tree does not merge record ownership or grant cross-unit inventory access. If units need operationally separate trees at one address, create separate site records or distinct building roots and associate them intentionally.

## NDC cutover

`EpfoNdcHierarchySeeder` idempotently creates `EPFO`, its `NDC` child, and `NDC_HQ`, then associates the NDC unit with that site.

`epfo:map-ndc-inventory` has four mutually exclusive operational modes:

- `--dry-run` performs the complete mapping in a transaction and rolls it back.
- `--apply` writes the mapping; omitting all mode flags remains an apply for backward compatibility.
- `--resume` processes unresolved records and incorrect role mappings while leaving already-correct memberships untouched.
- `--verify` is read-only and returns failure while baseline records, location sites, asset owners, user contexts, role scopes, or location paths remain invalid.

The mapping preserves legacy location values, fills missing site IDs, rebuilds repairable materialized paths, fills missing asset ownership with NDC, and assigns users through an explicit role mapping. Administrators receive descendant write scope; inventory, stock, and finance operators receive local write scope; viewers and auditors receive no write scope.

## Business Record Visibility Contract

Files, agreements, and tasks carry nullable transitional `organizational_unit_id` ownership. Payments inherit ownership exclusively from their agreement. Officials inherit visibility from their location and site mappings. Attachments inherit visibility only from a recognized, policy-protected parent (asset, location, file, agreement, task, or spatial map); unknown attachment parents fail closed.

All interactive queries receive an explicit `User`; no visibility scope reads ambient `Auth` state. Work without a user—console commands, queued jobs, service API clients, and legacy imports—must carry an `OrganizationalServiceIdentity` containing an execution channel, auditable purpose, and either explicit active unit IDs or an intentional organization-wide grant. Missing and inactive ownership is never visible, including to administrators or organization-wide service identities.

User-facing collections, searches, dashboard aggregates, exports, relationships, and audit feeds apply the same scopes. Route model binding remains unscoped so a real but unauthorized direct resource is consistently rejected by its policy with HTTP 403 rather than being disguised as HTTP 404.

The NDC mapping reads and writes only the target connection. It never queries or mutates `legacy`.

## Legacy-source safety

The `legacy` connection uses separate `LEGACY_DB_*` credentials. Its user must be granted `SELECT` only. The connection also initializes MySQL/MariaDB sessions as transaction read-only. Importers issue source queries through this connection and write only through the target connection.

PHPUnit always uses an in-memory SQLite target. Importer fixture tests explicitly replace the legacy connection with their isolated test fixture connection. `RefreshDatabase` must never point to a developer's XAMPP database.

## Asset placement and future modules

Assets retain `location_id` as the denormalized current position and receive nullable `organizational_unit_id` during cutover. `asset_placements` records history. Relocation is transactional, locks the asset, closes the old open placement, creates the new one, and checks source and destination permissions.

Consumables remain a shared catalog until per-location balances and immutable transactions are introduced. Spatial maps remain private attachments with authorization at the application route. Map coordinates, restricted rooms, rack positions, network data, and exact GPS are sensitive even when the parent asset is visible.

## Acceptance invariants

- No hierarchy change depends on ambient authentication or model events.
- `users.location_id` is not required.
- Legacy location columns and `assets.location_id` remain intact during transition.
- Paths are delimiter-safe and subtree moves are atomic.
- Structured locations obey parent type and site consistency rules.
- Shared site roots do not grant organizational inventory scope.
- NDC seed/apply/resume operations are idempotent.
- Verification performs no writes and reports explicit unresolved IDs.
- The source legacy connection is protected by configuration, credentials, and tests.
