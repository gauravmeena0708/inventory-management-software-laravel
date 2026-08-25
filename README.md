# Enterprise Inventory Management & Asset Tracking System

A modernized, enterprise-grade IT Inventory Management, Asset Lifecycle Tracking, Consumables Stock Ledger, and Vendor Contract Administration platform built on **Laravel 11/13**, **Tailwind CSS**, **Vite**, and **PHP 8.2+**.

---

## Key Modernized Architecture & Features

- **Unified Asset Model**: Single consolidated asset repository with polymorphic classification (`laptop`, `desktop`, `server`, `switch`, `storage`, `other`), specification metadata, and strict lifecycle state machines (`in_stock`, `in_use`, `under_maintenance`, `decommissioned`).
- **Chain-of-Custody Assignment Tracking**: Explicit asset assignment history linking assets to staff officials with condition-out/condition-in inspection logs, auto-closing previous assignments upon reassignment.
- **Immutable Stock Ledger**: Append-only transaction log (`entries`) with pessimistic database locking (`lockForUpdate`) and idempotency keys to prevent race conditions and duplicate deductions.
- **Automated Milestone Payment Schedules**: Idempotent milestone billing generation supporting monthly, quarterly, semi-annual, and annual frequencies with progressive `paid_till` reconciliation.
- **6-Role Granular Permission Matrix (RBAC)**: Policy-backed authorization for `Admin`, `Inventory Manager`, `Stock Operator`, `Finance Operator`, `Auditor`, and `Viewer`.
- **Personnel PII Encryption**: Synthetic-ready personnel models with field-level encryption for sensitive contact records and encrypted personal identity fields.
- **Private & Secure Document Vault**: Multi-disk private storage service with MIME validation, randomized hash naming, and time-limited authenticated access streams.
- **Tabular Data Exports**: Streamed Excel/CSV generation (`AssetsExport`, `AgreementsExport`) via `maatwebsite/excel` supporting filtered scopes and field formatting.
- **Enterprise Activity Logging**: Complete audit trail recording actors, subjects, state diffs, and timestamped lifecycle events powered by `spatie/laravel-activitylog`.
- **Read-Only Legacy ETL Importer**: Resilient 7-table data migration command with dry-run simulations, checksum verification, and resume checkpoints (`php artisan inventory:import-legacy`).

---

## Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend Framework** | Laravel 11 / Laravel 13 on PHP 8.2+ (tested up to PHP 8.4) |
| **Database** | MySQL 8.0+ / PostgreSQL 15+ / SQLite 3 (Default local/testing) |
| **Frontend & Bundling** | Vite, Tailwind CSS v4, Blade, Livewire Flux, Alpine.js |
| **Excel & Exporting** | `maatwebsite/excel` |
| **Audit & Logging** | `spatie/laravel-activitylog` |
| **Testing Engine** | PHPUnit 11/12, Orchestra Testbench |

---

## Quick Start & Installation

### 1. Prerequisites
- PHP `>= 8.2` with `mbstring`, `pdo`, `openssl`, `bcmath`, `xml`, `zip` extensions.
- Composer 2.x
- Node.js `>= 18.x` & NPM

### 2. Clone and Setup Environment
```bash
# Clone the repository
git clone https://github.com/gauravmeena0708/inventory-management-software-laravel.git
cd inventory-management-software-laravel

# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Create environment configuration
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup & Synthetic Fixtures
Configure your database credentials in `.env` (or use the default SQLite database), then run migrations and synthetic seeders:

```bash
# Run database migrations and seed synthetic faker dataset
php artisan migrate --seed
php artisan db:seed --class=EpfoNdcHierarchySeeder
php artisan epfo:map-ndc-inventory --apply

# Optional: add a broad local-only hierarchy of zones, regions, districts,
# vigilance, training, directorate, division, and section records.
php artisan db:seed --class=EpfoHierarchyDemoSeeder
```

### 4. Build Assets & Start Development Server
```bash
# Terminal 1: Build frontend assets
npm run dev

# Terminal 2: Run Laravel development server
php artisan serve
```

The application will be accessible at `http://127.0.0.1:8000`.

---

## Default Synthetic User Personas

The synthetic database seeder (`FictionalDatabaseSeeder`) provisions 6 privacy-compliant role accounts (all default to password: `password`):

| Email | Role | Granted Capabilities |
| :--- | :--- | :--- |
| `admin@inventory.local` | **Admin** | Full system access, user management, audit review, asset & financial mutation |
| `manager@inventory.local` | **Inventory Manager** | Asset CRUD, staff assignments, consumable definitions, agreement management |
| `stock@inventory.local` | **Stock Operator** | Post purchase & issue entries to stock ledger, view inventory balances |
| `finance@inventory.local` | **Finance Operator** | Vendor agreements, generate payment schedules, complete & verify invoices |
| `auditor@inventory.local` | **Auditor** | Read-only access across all modules, export reports, inspect activity log history |
| `viewer@inventory.local` | **Viewer** | Basic read-only dashboard & asset listings, no mutation or export rights |

---

## Automated Test Suite

The test suite covers unit and end-to-end feature workflows, authorization matrices, stock ledger concurrency, payment schedules, encrypted personnel, and the legacy ETL importer.

```bash
# Run the complete test suite
php artisan test

# Run specific test suites
php artisan test --filter=EndToEndInventoryFlowTest
php artisan test --filter=StockLedgerConcurrencyTest
php artisan test --filter=AuthorizationPolicyTest
php artisan test --filter=AgreementPaymentScheduleTest
php artisan test --filter=LegacyImporterVerificationTest
```

---

## Legacy Importer CLI Command

The modernization engine includes a read-only ETL command to migrate legacy inventory data (servers, desktops, laptops, devices, storages, consumables, agreements, payments) into the unified modernized schema.

```bash
# 1. Run in dry-run simulation mode (no changes committed)
php artisan inventory:import-legacy --dry-run

# 2. Execute full migration run
php artisan inventory:import-legacy

# 3. Resume an interrupted import from the last successful checkpoint
php artisan inventory:import-legacy --resume

# 4. Verify reconciliation counts, financial parity, and stock integrity without importing
php artisan inventory:import-legacy --verify
```

### Verification Metrics Checked by Importer
- **Entity Parity**: Validates record counts across legacy and unified tables.
- **Financial Parity**: Reconciles legacy payment totals with modernized payments.
- **Stock Discrepancy Detection**: Validates ledger entry balances against cached stock totals.

---

## API & Web Endpoints

### Core Resources
- `/dashboard` — KPI metrics, asset allocation counters, stock alerts, overdue payment notices.
- `/assets` — Unified asset management with type filtering (`laptop`, `desktop`, `server`, `switch`, `storage`).
- `/assets/{asset}/assign` — Assign asset to official with condition logging.
- `/assets/{asset}/return` — Return asset to stock with return notes and condition checks.
- `/assets/{asset}/decommission` — Decommission asset from inventory.
- `/consumables` & `/stock` — Consumables catalog and immutable stock ledger.
- `/consumables/{consumable}/entries` — Post stock purchase, issue, or adjustment entry.
- `/agreements` — Vendor contracts and AMC tracking.
- `/payments` — Milestone payment schedules, due payment monitoring, and invoice completion.
- `/assets/export` & `/agreements/export` — Streamed Excel tabular data exports.

---

## License

This software is open-sourced under the [MIT License](LICENSE).
