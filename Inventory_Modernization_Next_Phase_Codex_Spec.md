# Inventory Management Modernization — Next-Phase Implementation Specification

## Purpose

This document defines the next major implementation phase for the modernized inventory-management application.

The current application architecture is already on the right path:

- unified `Asset` domain;
- asset assignment history;
- placement history;
- organization/site/location hierarchy;
- organizational visibility and descendant read scopes;
- immutable stock ledger;
- agreements and payments;
- private attachments;
- activity logging;
- role/policy-based authorization;
- read-only legacy migration and reconciliation;
- current Laravel/PHP platform baseline.

The purpose of this phase is **not** to redesign the application again.

The purpose is to complete the operational, accountability, reporting, verification, maintenance, transfer, disposal, audit, and governance capabilities required for a serious government inventory/IT asset-management system.

The implementation should preserve the existing architecture and extend it carefully.

---

# 1. High-Level Design Principle

The system should answer the following questions reliably:

```text
WHAT does the organisation own?

WHERE is it physically located?

WHICH organisational unit owns or controls it?

WHO currently holds it?

HOW did it enter inventory?

WHAT has happened to it during its lifecycle?

IS it under warranty / AMC / support?

HAS it been physically verified?

HAS it ever been transferred, repaired, lost, condemned or disposed?

WHICH agreement covers it?

WHAT discrepancies or audit observations exist?

WHAT did the office report or certify at a given point in time?

WHAT can a higher office see about subordinate offices?
```

Every important answer should be traceable to source transactions, history, attachments and audit records.

---

# 2. Scope of This Phase

Implement the following major capabilities:

1. Physical Inventory Verification
2. Maintenance & Repair Management
3. Asset Transfer Workflow
4. Condemnation & Disposal Workflow
5. Agreement-to-Asset Coverage Mapping
6. QR / Barcode Asset Tagging
7. Procurement / Acquisition Provenance
8. Unified Asset Lifecycle Timeline
9. Alerts & Notifications Framework
10. Asset Relationships / Components
11. Bulk Operations & Bulk Import
12. Inventory Data Quality / Completeness
13. Extensible Asset Categories
14. Improved Asset Lifecycle State Model
15. Formal Reporting Platform
16. Official / Certified Report Snapshots
17. Hierarchical Reports for Higher Offices
18. Audit & Inspection Management
19. Audit Observation / Para Lifecycle
20. ATR — Action Taken Report
21. Management Dashboards with Drill-Down
22. Official Government-Style Inventory Registers
23. Report Authorization and Organizational Scoping

These capabilities should be implemented incrementally and should reuse the current authorization and organizational hierarchy wherever possible.

---

# 3. Existing Architecture to Preserve

Do not remove or weaken the following architectural decisions unless a test demonstrates a concrete defect.

## 3.1 Unified Asset Domain

Do not return to separate CRUD applications for:

```text
Desktop
Laptop
Server
Storage
Switch
Device
```

Continue using the common `Asset` domain.

Legacy classes may remain temporarily for migration compatibility, but new functionality must use the unified Asset model.

---

## 3.2 Organizational Scoping

Continue using:

```text
organizational_unit_id
local read scope
descendants read scope
local write scope
descendants write scope
```

Higher-office visibility should be derived from the current organizational hierarchy.

Do not create a second unrelated reporting-permission framework.

---

## 3.3 Immutable Transaction History

Historical transactions should not be silently overwritten.

Examples:

- stock ledger entries;
- asset assignments;
- transfers;
- physical verification results;
- maintenance events;
- disposal decisions;
- audit observation status changes;
- certified reports.

Corrections should use explicit correcting or superseding events wherever practical.

---

## 3.4 Evidence and Auditability

Continue using:

- attachments;
- activity log;
- explicit actor identity;
- timestamps;
- organizational ownership;
- source/reference numbers;
- hashes where required.

---

# 4. Asset Categories: Replace Hard-Coded Detailed Asset Types

The existing `AssetType` enum is useful for broad classification but should not become the long-term inventory taxonomy.

Do not require a code deployment merely to add:

```text
Printer
Monitor
UPS
Firewall
Router
Scanner
Tablet
Biometric Device
HSM
Load Balancer
GPU Workstation
CCTV Equipment
Projector
```

## 4.1 Introduce `AssetCategory`

Suggested model:

```text
asset_categories
  id
  code
  name
  parent_id nullable
  broad_family nullable
  description nullable
  is_active
  created_at
  updated_at
```

Examples:

```text
COMPUTE
  ├── Desktop
  ├── Laptop
  ├── Workstation
  └── Server

NETWORK
  ├── Switch
  ├── Router
  ├── Firewall
  └── Load Balancer

POWER
  ├── UPS
  └── PDU
```

The existing `AssetType` may remain temporarily as a broad family enum.

Add:

```text
assets.asset_category_id
```

Migrate existing asset types into corresponding categories.

---

# 5. Asset Lifecycle State Model

The current state model is too small for long-term operation.

Keep status reasonably broad.

Recommended statuses:

```text
IN_STOCK
RESERVED
IN_USE
IN_TRANSIT
UNDER_MAINTENANCE
MISSING
PENDING_DISPOSAL
DECOMMISSIONED
DISPOSED
```

Do not encode every lifecycle detail in `status`.

Use lifecycle events for detailed history.

---

# 6. Unified Asset Lifecycle Event Timeline

Introduce an event stream that allows the application to display one authoritative timeline.

## 6.1 Suggested model

```text
asset_lifecycle_events
  id
  asset_id
  event_type
  occurred_at
  actor_user_id nullable

  from_status nullable
  to_status nullable

  from_organizational_unit_id nullable
  to_organizational_unit_id nullable

  from_location_id nullable
  to_location_id nullable

  reference_type nullable
  reference_id nullable
  reference_number nullable

  remarks nullable
  metadata json nullable

  created_at
```

Suggested `event_type` values:

```text
ACQUIRED
RECEIVED
REGISTERED
ASSIGNED
RETURNED
TRANSFER_INITIATED
TRANSFER_DISPATCHED
TRANSFER_RECEIVED
PLACED
RELOCATED
MAINTENANCE_OPENED
MAINTENANCE_COMPLETED
VERIFIED
VERIFICATION_EXCEPTION
MISSING_REPORTED
MISSING_RESOLVED
DECOMMISSIONED
CONDEMNATION_RECOMMENDED
CONDEMNATION_APPROVED
DISPOSAL_INITIATED
DISPOSED
AGREEMENT_ATTACHED
AGREEMENT_REMOVED
CATEGORY_CHANGED
DATA_CORRECTED
OTHER
```

The lifecycle event stream should normally be created by domain services rather than directly from controllers.

---

# 7. Physical Inventory Verification

This is a critical capability.

The system should support formal physical verification campaigns.

## 7.1 Models

```text
inventory_verifications
  id
  organizational_unit_id
  site_id nullable
  name
  financial_year nullable
  verification_type
  planned_from nullable
  planned_to nullable
  started_at nullable
  completed_at nullable
  status
  created_by
  approved_by nullable
  remarks nullable
```

Suggested status:

```text
DRAFT
PLANNED
IN_PROGRESS
COMPLETED
CERTIFIED
CANCELLED
```

Create:

```text
inventory_verification_items
  id
  inventory_verification_id
  asset_id nullable

  expected_organizational_unit_id nullable
  observed_organizational_unit_id nullable

  expected_location_id nullable
  observed_location_id nullable

  expected_official_id nullable
  observed_official_id nullable

  result
  verified_by
  verified_at
  remarks nullable

  photo_attachment_id nullable
```

Suggested results:

```text
VERIFIED
NOT_FOUND
WRONG_LOCATION
WRONG_CUSTODIAN
DAMAGED
UNREGISTERED
SERIAL_MISMATCH
TAG_MISMATCH
OTHER_EXCEPTION
```

## 7.2 Workflow

```text
Create Verification Campaign
        ↓
Freeze expected inventory population / snapshot
        ↓
Scan / verify assets
        ↓
Record discrepancies
        ↓
Reconciliation
        ↓
Certification
        ↓
Final Verification Report
```

Once certified, the verification results should remain historically reproducible.

---

# 8. QR / Barcode Asset Tagging

Support QR labels for assets.

Each asset should have a stable scan URL.

Example:

```text
/assets/{asset}
```

Do not put sensitive data directly inside the QR code.

The QR should normally encode only a stable internal URL or opaque public-safe identifier.

## 8.1 Required capabilities

- single asset label generation;
- batch label generation;
- printable label sheets;
- QR scanning from mobile browser;
- scan-to-open asset page;
- scan mode inside physical verification;
- optional barcode support.

Label should include:

```text
Organisation
Asset Tag
Asset Category
QR Code
Optional Serial Number
```

---

# 9. Maintenance & Repair Management

Asset status alone is not sufficient.

Introduce maintenance as a first-class domain.

## 9.1 Suggested model

```text
maintenance_tickets
  id
  asset_id
  organizational_unit_id

  ticket_number
  issue_category nullable
  issue_description

  reported_by_user_id nullable
  reported_by_official_id nullable
  reported_at

  status
  severity nullable

  agreement_id nullable
  vendor_id nullable
  external_reference nullable

  sent_for_repair_at nullable
  repair_started_at nullable
  completed_at nullable
  returned_at nullable

  diagnosis nullable
  resolution nullable

  cost decimal nullable
  currency default INR
  covered_under_warranty boolean
  covered_under_amc boolean

  downtime_minutes nullable
  remarks nullable
```

Suggested statuses:

```text
OPEN
ACKNOWLEDGED
AWAITING_VENDOR
UNDER_REPAIR
AWAITING_PART
RESOLVED
RETURNED
CLOSED
CANCELLED
```

## 9.2 Maintenance History

Asset show page should contain:

```text
Maintenance History
Date
Issue
Vendor
Agreement
Cost
Downtime
Resolution
Status
```

---

# 10. Asset Transfer Workflow

Assignment to an individual and transfer between offices are different concepts.

Introduce a formal transfer domain.

## 10.1 Suggested model

```text
asset_transfers
  id
  transfer_number

  asset_id

  from_organizational_unit_id
  to_organizational_unit_id

  from_location_id nullable
  to_location_id nullable

  initiated_by
  approved_by nullable

  status

  requested_at
  approved_at nullable
  dispatched_at nullable
  received_at nullable

  received_by nullable

  condition_at_dispatch nullable
  condition_at_receipt nullable

  reference_number nullable
  remarks nullable
```

Suggested statuses:

```text
DRAFT
PENDING_APPROVAL
APPROVED
IN_TRANSIT
RECEIVED
REJECTED
CANCELLED
```

Do not update final location/ownership merely when a transfer is requested.

Final organizational ownership/location should normally update at receipt or another explicitly configured transition.

Support attachments such as transfer order, challan, acknowledgement or receipt.

---

# 11. Condemnation and Disposal Workflow

Do not treat `DECOMMISSIONED` as equivalent to disposed.

Introduce disposal lifecycle.

## 11.1 Suggested model

```text
asset_disposals
  id
  asset_id

  status
  recommendation_date nullable
  recommended_by nullable

  committee_reference nullable
  inspection_reference nullable

  approval_date nullable
  approved_by nullable
  approval_reference nullable

  disposal_method nullable
  disposal_vendor nullable
  auction_reference nullable

  disposal_date nullable
  sale_value nullable

  data_destruction_required boolean
  data_destruction_completed_at nullable
  data_destruction_certificate_attachment_id nullable

  disposal_certificate_attachment_id nullable
  remarks nullable
```

Suggested status:

```text
RECOMMENDED
UNDER_REVIEW
APPROVED
PENDING_DISPOSAL
DISPOSED
REJECTED
CANCELLED
```

Suggested disposal methods:

```text
E_WASTE
AUCTION
TRANSFER
SCRAP
DESTRUCTION
RETURN_TO_VENDOR
OTHER
```

For storage media and systems containing departmental data, support evidence of secure data destruction.

---

# 12. Agreement-to-Asset Coverage Mapping

The Agreement domain must support actual asset coverage.

Create a many-to-many association.

```text
agreement_asset
  id
  agreement_id
  asset_id

  coverage_type nullable
  coverage_start nullable
  coverage_end nullable

  sla_reference nullable
  remarks nullable
```

Examples of coverage:

```text
WARRANTY
AMC
COMPREHENSIVE_AMC
SUPPORT
LICENSE
CLOUD_SERVICE
OTHER
```

Agreement detail page should show:

```text
Assets Covered
Asset Tag
Category
Serial
Coverage Start
Coverage End
Current Status
Maintenance Calls
```

Asset page should show:

```text
Active Warranty / AMC / Support Contracts
```

Maintenance tickets should be able to link to the applicable agreement.

---

# 13. Procurement / Acquisition Provenance

Do not build a full procurement approval application in this phase.

However, inventory should preserve acquisition provenance.

## 13.1 Suggested models

```text
acquisitions
  id
  organizational_unit_id

  acquisition_type
  vendor_name nullable
  vendor_id nullable

  gem_order_number nullable
  purchase_order_number nullable
  sanction_reference nullable
  invoice_number nullable
  invoice_date nullable
  grn_number nullable
  receipt_date nullable

  total_value nullable
  currency default INR

  file_id nullable
  remarks nullable
```

Create:

```text
acquisition_assets
  acquisition_id
  asset_id
  unit_cost nullable
  quantity_component nullable
```

This allows:

```text
GeM Order
    ↓
Invoice
    ↓
GRN
    ↓
Asset Batch
    ↓
Individual Asset Tags
```

---

# 14. Asset Relationships / Components

Support relationships between assets without forcing full CMDB complexity.

## 14.1 Suggested table

```text
asset_relationships
  id
  parent_asset_id
  child_asset_id
  relationship_type
  remarks nullable
```

Suggested relationship types:

```text
COMPONENT_OF
ACCESSORY_OF
INSTALLED_IN
CONNECTED_TO
BACKED_UP_BY
POWERED_BY
PART_OF_BUNDLE
REPLACED_BY
OTHER
```

Examples:

```text
Laptop
 ├── Charger
 └── Dock

Rack
 ├── Server
 ├── Switch
 └── Storage
```

Do not model every minor component unless operationally useful.

---

# 15. Bulk Operations

Enterprise operation requires bulk workflows.

Implement bulk capabilities for:

- asset creation/import;
- asset category update;
- organizational transfer;
- location update;
- assignment;
- agreement coverage mapping;
- physical verification;
- QR-label printing;
- decommissioning recommendation;
- export.

## 15.1 CSV / Spreadsheet Import

Create a reusable import framework.

Requirements:

```text
upload
validate
preview
show row-level errors
confirm
process
reconcile
download result
```

Do not perform partial silent imports without a reconciliation report.

Large imports should be queued.

---

# 16. Data Quality / Inventory Completeness

Introduce data-quality indicators.

## 16.1 Suggested completeness checks

Per asset:

```text
asset_tag
serial_number
category
manufacturer
model
organizational_unit
location
current custodian
purchase/acquisition record
warranty/support data
physical verification status
```

Provide a calculated completeness score or issue list.

Example:

```text
Asset completeness: 72%

✓ Asset Tag
✓ Serial Number
✓ Manufacturer
✓ Location
✕ Acquisition record
✕ Warranty data
⚠ Custodian requires verification
```

## 16.2 Organization-Level Data Quality Dashboard

Examples:

```text
98% have asset tags
92% have serial numbers
87% have physical locations
71% mapped to acquisition records
64% physically verified this financial year
```

Higher offices should be able to compare subordinate offices.

---

# 17. Alerts and Notifications Framework

Do not hard-code each alert independently in controllers.

Create reusable alert rules.

Examples:

```text
Warranty expires within 30 / 60 / 90 days
AMC expires within 30 / 60 / 90 days
End-of-support reached
Asset transfer pending receipt
Maintenance ticket open beyond SLA
Asset missing in physical verification
Verification overdue
Agreement expiring
Payment due
Low consumable stock
Audit para overdue
ATR reply overdue
```

Support:

- dashboard alerts;
- user notifications;
- optional email delivery;
- future scheduled notifications.

Respect organizational visibility.

---

# 18. Reporting Architecture

The current inventory-report screen is only a live MIS snapshot.

Build a formal reporting subsystem with three distinct concepts:

```text
1. LIVE MIS
2. OFFICIAL / PERIODIC REPORT
3. AUDIT / VERIFICATION REPORT
```

Do not treat all three as the same thing.

---

# 19. Report Families

Create five logical report families.

## 19.1 Registers

Examples:

```text
Asset Register
Fixed Asset Register
Consumable Stock Register
Agreement Register
Maintenance Register
Transfer Register
Disposal Register
```

These are generally live, but must support as-on-date generation where feasible.

---

## 19.2 MIS / Management Reports

Examples:

```text
Assets by office
Assets by category
Assets by status
Assets by location
Assets by custodian
Assets under maintenance
Assets beyond support
Assets with expiring warranty
Assets without active support
Low stock
Agreement expiry
Data-quality position
Physical-verification status
```

These reflect current data.

---

## 19.3 Periodic / Certified Reports

Examples:

```text
Monthly Inventory Return
Quarterly Inventory Position
Annual Asset Statement
Annual Consumable Statement
Annual IT Asset Position
```

These must support formal certification and frozen snapshots.

---

## 19.4 Verification Reports

Examples:

```text
Annual Physical Verification Report
Physical Verification Certificate
Missing Asset Statement
Wrong Location Statement
Wrong Custodian Statement
Damaged Asset Statement
Unregistered Asset Statement
Verification Reconciliation Statement
```

---

## 19.5 Audit & Compliance Reports

Examples:

```text
Audit Report
Open Audit Para Register
ATR
Outstanding Audit Para Statement
High-Risk Audit Observation Statement
Age-wise Audit Para Statement
Office-wise Audit Compliance Position
```

---

# 20. Report Definitions

Do not create 40 independent controllers for 40 report types.

Introduce reusable report definitions.

## 20.1 Suggested model

```text
report_definitions
  id
  code
  name
  category
  description nullable

  generator_class
  default_format
  allowed_formats json

  is_certifiable boolean
  supports_as_on_date boolean
  supports_period boolean
  is_active boolean
```

Example codes:

```text
INV-ASSET-REGISTER
INV-FIXED-ASSET-REGISTER
INV-CONSUMABLE-REGISTER
INV-PHYSICAL-VERIFICATION
INV-AMC-EXPIRY
INV-ASSET-AGEING
INV-OFFICE-SUMMARY
INV-DATA-QUALITY
AUD-OPEN-PARAS
AUD-ATR
```

---

# 21. Report Runs and Certified Snapshots

Official reports must not change merely because current data later changes.

Create `report_runs`.

## 21.1 Suggested model

```text
report_runs
  id
  report_definition_id

  organizational_unit_id
  scope_type

  reporting_from nullable
  reporting_to nullable
  as_on_date nullable
  data_cutoff_at

  parameters json nullable

  status

  generated_by
  generated_at

  prepared_by nullable
  prepared_at nullable

  verified_by nullable
  verified_at nullable

  approved_by nullable
  approved_at nullable

  version integer

  snapshot_data json
  rendered_file_attachment_id nullable

  sha256 nullable
  supersedes_report_run_id nullable

  remarks nullable
```

Suggested statuses:

```text
DRAFT
GENERATED
SUBMITTED
VERIFIED
APPROVED
FINAL
SUPERSEDED
CANCELLED
```

Once `FINAL`, prevent ordinary mutation of snapshot data.

If correction is required:

```text
Final Report v1
      ↓
Superseded by
Final Report v2
```

Do not silently overwrite history.

---

# 22. Organizational Report Visibility

Use the existing organization hierarchy.

Examples:

```text
Local office user:
  sees reports for own unit

Higher office user with descendant read scope:
  sees own + subordinate office reports

Administrator:
  organization-wide read
```

The current `OrganizationalVisibility` service should remain the common authority for report scope where possible.

Introduce report policy checks for:

```text
view
generate
submit
verify
approve
finalize
export
viewDescendants
```

A higher office should be able to generate consolidated reports for descendants if authorized.

---

# 23. Higher-Office Reporting

A higher office should not merely receive spreadsheets from lower offices.

The system should support live hierarchical consolidation.

Example:

```text
Zone
──────────────────────────────────────────────
Office       Assets   Verified   Missing   EoS
Jaipur       1,274     98.2%       12       63
Udaipur        624     95.4%        8       19
Jodhpur        917     91.7%       23       41
Kota           482     99.1%        2       12
```

Allow drill-down:

```text
Zone
 ↓
Office
 ↓
Report / Metric
 ↓
Asset / Transaction / Evidence
```

This traceability is mandatory.

---

# 24. Official Government-Style Reports

The system should support report templates suitable for formal departmental use.

Examples include:

```text
Fixed Asset Register
Consumable Stock Register
Annual Physical Verification Certificate
Annual Verification Report
Shortage / Excess Statement
Unserviceable Asset Statement
Disposal Statement
Agreement / AMC Register
```

Templates should be configurable and not hard-coded into database migrations.

For reports derived from official Government of India forms or GFR requirements, preserve:

- form title;
- report date;
- office name;
- financial year;
- certification text;
- signature blocks;
- page numbering;
- annexures;
- report number/reference;
- generated date/time.

Do not claim a report is an official statutory form unless its template has been verified.

---

# 25. Audit & Inspection Domain

Audit should be implemented as a separate formal subsystem.

Do not equate:

```text
system anomaly
```

with:

```text
audit observation
```

A system exception may be evidence for an audit observation, but an authorized auditor should create the formal observation.

---

# 26. Audit Engagement

## 26.1 Suggested model

```text
audit_engagements
  id

  audit_number
  audit_type

  audited_organizational_unit_id
  auditing_organizational_unit_id nullable

  audit_from nullable
  audit_to nullable

  fieldwork_started_at nullable
  fieldwork_completed_at nullable

  status

  lead_auditor_user_id nullable
  created_by

  scope_text nullable
  report_date nullable
  remarks nullable
```

Suggested audit types:

```text
INTERNAL_AUDIT
INVENTORY_AUDIT
PHYSICAL_VERIFICATION_AUDIT
IT_ASSET_REVIEW
SPECIAL_AUDIT
INSPECTION
OTHER
```

Suggested status:

```text
PLANNED
IN_PROGRESS
DRAFT_REPORT
OBSERVATIONS_ISSUED
REPLIES_PENDING
UNDER_COMPLIANCE
CLOSED
CANCELLED
```

---

# 27. Audit Observations / Paras

## 27.1 Suggested model

```text
audit_observations
  id
  audit_engagement_id

  para_number
  title
  finding

  severity
  risk_category nullable

  financial_implication nullable
  currency default INR

  status

  issued_at nullable
  due_date nullable

  created_by
  closed_by nullable
  closed_at nullable

  closure_reason nullable
```

Suggested severity:

```text
LOW
MEDIUM
HIGH
CRITICAL
```

Suggested status:

```text
DRAFT
ISSUED
REPLY_RECEIVED
UNDER_EXAMINATION
PARTLY_SETTLED
OUTSTANDING
SETTLED
DROPPED
```

---

# 28. Audit Observation Relationships

Allow an observation to link to:

- assets;
- agreements;
- maintenance tickets;
- transfers;
- stock transactions;
- physical-verification items;
- disposals;
- reports.

Use appropriate pivot/link tables.

Example:

```text
audit_observation_assets
  audit_observation_id
  asset_id
```

---

# 29. Audit Evidence

Create reusable evidence links.

```text
audit_evidence
  id
  audit_observation_id

  attachment_id nullable

  evidence_type
  description nullable

  source_model_type nullable
  source_model_id nullable

  created_by
  created_at
```

Evidence may include:

- screenshots;
- asset lists;
- stock statements;
- verification records;
- office orders;
- replies;
- invoices;
- agreements;
- maintenance records.

---

# 30. Audit Replies and Remarks

Do not overwrite one text field repeatedly.

Create a correspondence history.

## 30.1 Suggested model

```text
audit_responses
  id
  audit_observation_id

  response_type

  organizational_unit_id
  submitted_by

  body
  submitted_at

  attachment_id nullable
```

Suggested response types:

```text
OFFICE_REPLY
AUDITOR_REMARK
SUPPLEMENTARY_REPLY
HIGHER_OFFICE_REMARK
FINAL_DECISION
```

This provides a complete chronological audit trail.

---

# 31. ATR — Action Taken Report

ATR should be generated natively from audit observations and replies.

Support ATR for:

```text
one audit
one office
one zone
one organizational subtree
organization-wide
```

ATR columns may include:

```text
Audit
Para
Observation
Severity
Date issued
Office reply
Latest auditor remark
Current status
Pending since
Financial implication
Action required
```

Allow:

```text
ATR as on date
```

and certified/frozen ATR reports where needed.

---

# 32. Audit Reporting Hierarchy

Higher offices should be able to see lower-office audit positions.

Example:

```text
Zone Audit Position

Office       Open   >3 months   >1 year   High Risk
Jaipur          8        3          1          2
Udaipur         2        0          0          0
Jodhpur        14        7          3          4
Kota            1        0          0          0
```

Allow drill-down:

```text
Office
 ↓
Audit
 ↓
Observation
 ↓
Reply
 ↓
Evidence
 ↓
Affected Asset
```

---

# 33. Management Dashboard

Create a management-oriented dashboard separate from the detailed transaction screens.

Example:

```text
INVENTORY GOVERNANCE

Assets
In Use
In Stock
Under Maintenance
Beyond Support
Unverified
Missing
Pending Disposal

Agreements Expiring
Payments Due

Open Maintenance Tickets
Open Transfers
Pending Receipts

Physical Verification %
Data Completeness %

Open Audit Paras
High-Risk Paras
Paras > 1 Year
```

For higher offices, metrics should aggregate descendants.

Every metric should drill down to the underlying records.

---

# 34. Local Office Reporting Experience

A local office should see:

```text
Reports
├── Live MIS
├── Asset Registers
├── Stock Reports
├── Annual Reports
├── Physical Verification
├── Agreements / AMC
├── Maintenance
├── Transfers
├── Disposal
└── Audit & Inspection
```

The office should be able to:

- generate its reports;
- review its own previous certified reports;
- see its open audit observations;
- submit replies;
- upload evidence;
- generate ATR;
- track compliance.

---

# 35. Higher-Office Reporting Experience

A higher office with descendant scope should be able to:

```text
Select organizational scope:
  Current office only
  Current office + descendants
  Specific subordinate office
```

For reports:

```text
view
compare
consolidate
drill down
export
```

For audit:

```text
view subordinate audit status
issue observation if authorized
review replies
record remarks
settle / keep outstanding
generate consolidated ATR
```

Write access must remain separately controlled.

---

# 36. Reporting Formats

Support the following progressively:

```text
HTML
XLSX
PDF
CSV where useful
```

HTML should be the canonical interactive view.

XLSX should support analysis.

PDF should be used for certified/final reports.

Generated final PDFs should be stored as private attachments.

---

# 37. Report Integrity

For certified final reports:

- store snapshot data;
- store generation parameters;
- store data cutoff timestamp;
- store generated PDF;
- calculate SHA-256;
- record approver;
- record finalization time;
- prevent silent replacement.

Display:

```text
Report Version
Finalized At
Approved By
SHA-256
Superseded / Current
```

---

# 38. Roles and Permissions

Continue using existing roles, but expand permission-level checks.

Possible roles:

```text
ADMIN
INVENTORY_MANAGER
STOCK_OPERATOR
FINANCE_OPERATOR
VIEWER
AUDITOR
```

Consider adding future roles only if actual permission differences require them.

Do not create role explosion where policies/permissions are sufficient.

Important permissions:

```text
assets.view
assets.manage

verification.view
verification.manage
verification.certify

maintenance.view
maintenance.manage

transfer.view
transfer.initiate
transfer.approve
transfer.receive

disposal.view
disposal.recommend
disposal.approve
disposal.complete

reports.view
reports.generate
reports.submit
reports.verify
reports.approve
reports.finalize
reports.export

audit.view
audit.create
audit.issue_observation
audit.reply
audit.review_reply
audit.settle
audit.view_descendants
```

Use policies and organizational scope together.

---

# 39. Search

Expand search beyond the current basic asset search.

Global search should eventually support:

```text
Asset Tag
Serial Number
Model
Official
Location
Agreement
Audit Para
Transfer Number
Maintenance Ticket
Report Number
Invoice
GeM Order
```

Do not implement external search infrastructure unless required by scale.

---

# 40. Attachments

Continue using private attachments.

Allow attachments on:

- assets;
- agreements;
- maintenance tickets;
- transfers;
- disposal records;
- acquisitions;
- verification campaigns;
- audit observations;
- audit responses;
- report runs.

Every download should pass authorization.

---

# 41. Audit Log

All important mutations should produce an application audit event.

High-value events include:

```text
Asset created
Asset edited
Asset assigned
Asset returned
Asset transferred
Transfer approved
Asset received
Maintenance opened/closed
Physical verification result changed
Verification certified
Asset decommissioned
Disposal approved
Asset disposed
Agreement coverage changed
Report generated
Report certified
Report superseded
Audit observation issued
Audit reply submitted
Audit status changed
Audit para settled
```

---

# 42. Reports Must Be Reproducible

Do not build official reports by querying only current mutable rows at render time after certification.

A final report must have sufficient stored snapshot data to reproduce what was certified.

For very large reports, snapshot strategy may use:

- frozen report rows;
- report result tables;
- compressed JSON;
- generated file plus manifest;
- a combination.

Choose implementation based on size and query performance.

---

# 43. Spatial Mapping Priority

Keep the current spatial-map capability, but do not prioritize major new floor-plan/map features until the following are stable:

```text
asset lifecycle
verification
maintenance
transfer
disposal
agreement coverage
reporting
audit
```

Do not delete existing spatial functionality.

Treat it as an optional advanced module.

---

# 44. Non-Goals for This Phase

Do not implement unless separately approved:

- full procurement approval workflow;
- government financial accounting;
- depreciation calculations;
- automatic network device discovery;
- full CMDB dependency discovery;
- full IT helpdesk/ITSM platform;
- native mobile application;
- advanced GIS;
- AI assistant;
- predictive maintenance;
- automated audit findings;
- facial recognition;
- external public portal.

---

# 45. Recommended Implementation Order

## Phase A — Core Asset Accountability

1. AssetCategory
2. lifecycle status cleanup
3. AssetLifecycleEvent
4. Agreement ↔ Asset
5. Maintenance
6. Transfer
7. Disposal

---

## Phase B — Verification & Operations

8. QR/barcode labels
9. Physical Verification
10. Bulk operations
11. Acquisition provenance
12. Data-quality dashboard
13. Alerts

---

## Phase C — Reporting Foundation

14. ReportDefinition
15. ReportRun
16. Live hierarchical MIS
17. Certified report workflow
18. PDF/XLSX generation
19. report integrity/hash
20. formal inventory registers

---

## Phase D — Audit & Compliance

21. AuditEngagement
22. AuditObservation
23. AuditEvidence
24. AuditResponse
25. ATR
26. descendant audit dashboards
27. aging/high-risk reporting

---

## Phase E — UX Hardening

28. unified management dashboard
29. drill-down navigation
30. accessibility
31. mobile-responsive QR/verification experience
32. performance tuning
33. security review
34. browser tests

---

# 46. Acceptance Criteria — Asset Accountability

The implementation is acceptable when:

- every asset can be assigned and returned with history;
- every inter-office transfer has a stateful workflow;
- maintenance history is retained;
- agreements can cover assets;
- disposal is distinct from decommissioning;
- lifecycle timeline shows major historical events;
- categories can be added without changing application code;
- asset details show current owner, location, custodian, support and lifecycle state.

---

# 47. Acceptance Criteria — Physical Verification

The implementation is acceptable when:

- an office can create a verification campaign;
- expected assets are frozen for that campaign;
- verifier can scan QR code;
- verifier can record location/custodian mismatch;
- missing assets are recorded;
- reconciliation is possible;
- final campaign can be certified;
- certified verification report remains reproducible;
- higher offices can see subordinate verification status according to scope.

---

# 48. Acceptance Criteria — Reporting

The implementation is acceptable when:

- local office sees its own live reports;
- higher office sees descendant reports where permitted;
- reports can be filtered by organizational scope;
- reports drill down to source records;
- report definitions are reusable;
- formal reports can be frozen;
- final reports retain snapshot, approver, timestamp and integrity hash;
- superseded reports remain historically available;
- XLSX and PDF generation respect authorization.

---

# 49. Acceptance Criteria — Audit

The implementation is acceptable when:

- an audit engagement can be created;
- observations/paras can be issued;
- observations can link to assets and evidence;
- audited office can submit reply;
- auditor can record remarks;
- para status history is retained;
- para can be partly settled / outstanding / settled;
- ATR can be generated automatically;
- higher office can view consolidated open-para position;
- drill-down reaches evidence and source asset records.

---

# 50. Required Testing

Add automated tests for at least:

## Asset lifecycle

- create;
- assign;
- return;
- transfer;
- receive;
- maintenance;
- decommission;
- disposal;
- concurrent assignment/transfer conflict.

## Verification

- campaign snapshot;
- QR verification;
- mismatch recording;
- certification;
- authorization.

## Stock

Retain existing tests and add:

- bulk operations;
- report reconciliation.

## Reporting

- local scope;
- descendant scope;
- unauthorized office access;
- certified report immutability;
- report supersession;
- report snapshot reproducibility;
- export authorization.

## Audit

- create audit;
- issue para;
- reply;
- auditor remark;
- settle;
- organizational scoping;
- ATR generation;
- audit aging metrics.

## Security

- IDOR attempts;
- cross-office asset access;
- cross-office attachment access;
- report access;
- audit access;
- descendant-scope boundaries.

---

# 51. Migration Strategy

Do not rewrite the production database destructively.

Use migrations.

For existing assets:

- backfill categories from current asset type;
- create lifecycle events from available history where reliable;
- do not invent historical events;
- mark uncertain imported facts as legacy/inferred where required.

For agreements:

- retain current agreement records;
- coverage mapping may initially be empty;
- allow future bulk association.

For existing reports:

- current live inventory report remains available during transition.

---

# 52. UI Design Guidance

Prefer operational clarity over decorative dashboards.

Use:

- compact tables;
- filters;
- badges;
- status timelines;
- clear office scope indicator;
- breadcrumbs;
- evidence links;
- source drill-down;
- print-friendly report views.

Every page should make current organizational context obvious.

Examples:

```text
Scope: RO Jaipur
```

or:

```text
Scope: Rajasthan Zone + Descendants
```

Avoid accidental ambiguity about whether a count represents one office or an entire hierarchy.

---

# 53. Management Principle: Dashboard → Report → Evidence

Every summarized metric should support drill-down.

Example:

```text
Open Audit Paras: 283
        ↓
Zone
        ↓
Office
        ↓
Audit
        ↓
Para
        ↓
Reply / Evidence
        ↓
Affected Asset
```

Example:

```text
Missing Assets: 17
        ↓
Verification Campaign
        ↓
Asset
        ↓
Expected Location
        ↓
Observed Result
        ↓
Reconciliation History
```

This traceability is a core requirement.

---

# 54. Government Reporting Principle

The system should distinguish:

```text
CURRENT SYSTEM STATE

from

CERTIFIED HISTORICAL REPORT
```

A certified report represents what an office formally reported at a given point in time.

It must not automatically change after later asset corrections.

---

# 55. Audit Principle

The system should distinguish:

```text
SYSTEM EXCEPTION

from

FORMAL AUDIT OBSERVATION
```

A system may detect:

```text
14 assets with no physical location
```

That is an MIS/data-quality exception.

An auditor may formally issue:

```text
Inventory records are incomplete because 14 assets
lack recorded physical locations.
```

That is an audit observation.

The two may be linked but must not be automatically equated.

---

# 56. Coding-Agent Instructions

When implementing this specification:

1. Inspect the current `main` branch before each major change.
2. Preserve existing services and policies where they already solve the requirement.
3. Prefer application service/action classes for business mutations.
4. Keep controllers thin.
5. Use Form Requests for validation.
6. Use Policies for authorization.
7. Reuse `OrganizationalVisibility`.
8. Use database transactions for stateful mutations.
9. Use row locks where concurrency could create invalid state.
10. Preserve immutable historical data.
11. Avoid code duplication.
12. Avoid introducing new third-party packages unless the framework cannot reasonably provide the capability.
13. Add migrations, factories, seeders and tests for each new domain.
14. Keep legacy importer behavior read-only.
15. Do not remove old functionality until replacement behavior is tested.
16. Keep all uploaded/generated official files private.
17. Add indexes for all major reporting and hierarchy query paths.
18. Document all non-obvious architecture decisions.

---

# 57. Final Target

The application should evolve from:

```text
Inventory CRUD + asset tracking
```

into:

```text
ENTERPRISE INVENTORY GOVERNANCE PLATFORM

Assets
  +
Stock
  +
Custody
  +
Locations
  +
Contracts
  +
Maintenance
  +
Transfers
  +
Physical Verification
  +
Disposal
  +
Lifecycle History
  +
Official Reports
  +
Audit / Inspection
  +
ATR
  +
Higher-Office Consolidation
  +
Evidence / Traceability
```

The defining capability should be:

> **Any authorized office should be able to know the current inventory position, reproduce its certified historical reports, verify its physical assets, respond to audit observations, and allow authorized higher offices to obtain consolidated and drill-down reports for subordinate offices without requiring parallel spreadsheets or manual compilation.**
