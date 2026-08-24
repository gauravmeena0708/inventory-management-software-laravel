# Platform Baseline: Inventory Modernization

- **Date selected:** 2026-08-24
- **Status:** Proposed; revalidate before implementation and before production launch
- **Architecture:** [Inventory Modernization and Asset Consolidation](./2026-08-24-inventory-modernization-design.md)
- **Next mandatory review:** 2026-11-24 or before production launch, whichever occurs first

This record contains intentionally replaceable implementation versions. Changing this file does not change the architecture when the capability contracts and acceptance criteria in the architecture remain satisfied.

## Selected baseline

| Capability | Selected implementation | Constraint or rule |
| :--- | :--- | :--- |
| Runtime | PHP 8.4 | Pin a supported patch release in build/deployment images |
| Framework | Laravel 13 | Use the current compatible patch release and Composer lock file |
| Authentication/UI foundation | Current Laravel Livewire starter kit | Retain application-owned policies and services outside starter-kit code |
| Presentation | Blade with the starter-kit-supported reactive and styling stack | Use the versions generated and supported by the selected starter kit |
| Frontend build | Framework-supported Vite toolchain | Exact versions come from the frontend lock file |
| Database | MySQL 8.0 or later supported release | Production and integration tests use the same major behavior |
| Cache/queue/locks | Redis-compatible supported service | Required for multi-instance production deployment |
| Audit adapter | `spatie/laravel-activitylog` compatible release | Access only through the application `AuditRecorder` contract |
| Tabular export adapter | `maatwebsite/excel` compatible release | Access only through the application `TabularExporter` contract |
| Static analysis | Compatible Larastan/PHPStan release | No new unbaselined errors |
| Formatting | Compatible Laravel Pint release | Enforced in CI |

Exact dependency versions belong in `composer.lock` and the frontend lock file, not in this record.

## Selection rationale

- The selected framework release was the current supported Laravel major on the selection date.
- PHP 8.4 provides compatibility with the selected framework and the proposed current audit adapter.
- The Livewire starter kit preserves a server-rendered development model while providing maintained authentication and interactive UI foundations.
- Package-specific calls will be isolated behind application contracts so compatible packages can be upgraded or replaced independently.

## Revalidation checklist

Before starting implementation and before every production launch:

1. Confirm framework security support extends at least 12 months beyond the planned launch.
2. Confirm the PHP runtime is supported by PHP upstream, the framework, and all required extensions/packages.
3. Resolve Composer and frontend dependencies from clean lock files.
4. Review framework, runtime, package, and frontend security advisories.
5. Confirm the authentication/UI foundation is still maintained for new applications.
6. Run formatting, static analysis, unit, feature, browser, frontend-build, MySQL integration, migration, concurrency, and importer tests.
7. Confirm deployment images, MySQL, Redis-compatible services, and Node tooling are supported by their vendors.
8. Update the decision date, selected baseline, rationale, source links, and next review date.

If the support runway fails, select the newest stable compatible baseline that passes this checklist. Do not preserve a framework major merely because it appears in an older plan.

## Upgrade record template

For each baseline change, append:

```text
Decision date:
Previous baseline:
New baseline:
Support dates checked:
Package compatibility checked:
Adapters changed:
Migration/configuration changes:
Test evidence:
Rollback plan:
Approved by:
Next review date:
```

## Sources checked for this proposal

- [Laravel release notes and support policy](https://laravel.com/docs/13.x/releases)
- [Laravel starter kits](https://laravel.com/docs/13.x/starter-kits)
- [PHP supported versions](https://www.php.net/supported-versions.php)
- [Spatie Laravel Activitylog](https://packagist.org/packages/spatie/laravel-activitylog)
- [Laravel Excel](https://packagist.org/packages/maatwebsite/excel)
