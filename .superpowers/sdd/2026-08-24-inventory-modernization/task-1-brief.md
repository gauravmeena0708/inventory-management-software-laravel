# Task 1 Brief: Environment, Dependencies & Base Scaffolding

## Objective
Upgrade framework dependencies to Laravel 13.x on PHP 8.4+, modernize `composer.json` and `package.json`, set up dual database connections (`mysql` and read-only `legacy`), configure the current Laravel `bootstrap/app.php`, create `UserRole` enum, and implement `PlatformBaselineTest`.

## Specific Requirements
1. **`composer.json`**:
   - `php`: `^8.4`
   - `laravel/framework`: `^13.17`
   - `laravel/fortify`: `^1.37.2`
   - `livewire/livewire`: `^4.1`
   - `livewire/flux`: `^2.13.1`
   - `livewire/blaze`: `^1.0`
   - `spatie/laravel-activitylog`: `^5.1`
   - `maatwebsite/excel`: `^4.0`
   - `phpunit/phpunit`: `^12.5.23`
   - Remove obsolete packages: `fideloper/proxy`, `fruitcake/laravel-cors`, `facade/ignition`, `laravel/ui`, `laravel-mix`.
2. **`package.json`**:
   - Modern frontend dependencies aligned with the official starter kit: `vite: ^8.0`, `laravel-vite-plugin: ^3.1`, `tailwindcss: ^4.0`, `@tailwindcss/vite: ^4.1`, and `axios: ^1.13` while existing bootstrap code is ported.
   - Remove `laravel-mix`, `webpack`, and `node-sass`.
3. **`config/database.php`**:
   - Standard `mysql` connection.
   - Read-only `legacy` connection:
     ```php
     'legacy' => [
         'driver' => 'mysql',
         'url' => env('LEGACY_DATABASE_URL'),
         'host' => env('LEGACY_DB_HOST', env('DB_HOST', '127.0.0.1')),
         'port' => env('LEGACY_DB_PORT', env('DB_PORT', '3306')),
         'database' => env('LEGACY_DB_DATABASE', 'inventory_legacy'),
         'username' => env('LEGACY_DB_USERNAME', env('DB_USERNAME', 'forge')),
         'password' => env('LEGACY_DB_PASSWORD', env('DB_PASSWORD', '')),
         'charset' => 'utf8mb4',
         'collation' => 'utf8mb4_unicode_ci',
         'prefix' => '',
         'read_only' => true,
     ],
     ```
4. **`bootstrap/app.php`**:
   - Current Laravel format using `Application::configure(basePath: dirname(__DIR__))` with web/console routing and exception handling.
5. **`app/Enums/UserRole.php`**:
   - Backed string enum:
     - `ADMIN = 'admin'`
     - `INVENTORY_MANAGER = 'inventory_manager'`
     - `STOCK_OPERATOR = 'stock_operator'`
     - `FINANCE_OPERATOR = 'finance_operator'`
     - `VIEWER = 'viewer'`
     - `AUDITOR = 'auditor'`
6. **`tests/Unit/PlatformBaselineTest.php`**:
   - Tests `PHP_VERSION_ID >= 80400` (or the selected baseline's runtime compatibility).
   - Tests `config('database.connections.legacy')` exists and is read-only.
   - Tests `UserRole` cases match the 6 specified roles.

## Output Report Contract
Write complete execution notes and test verification output to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-1-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
