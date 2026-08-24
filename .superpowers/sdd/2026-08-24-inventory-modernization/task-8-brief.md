# Task 8 Brief: Audit Logging & Tabular Export Adapters

## Objective
Implement framework-isolated contracts for audit logging (`AuditRecorder`) and spreadsheet generation (`TabularExporter`), concrete adapters for Spatie Activitylog v4/v5 and Maatwebsite Excel, export definition classes (`AssetsExport`, `AgreementsExport`), service container bindings, and unit tests in `tests/Unit/AuditAndExportTest.php`.

## Specific Requirements
1. **Contracts (`app/Contracts/`)**:
   - `AuditRecorder.php`:
     ```php
     namespace App\Contracts;

     use Illuminate\Database\Eloquent\Model;
     use App\Models\User;

     interface AuditRecorder
     {
         public function record(string $event, ?Model $subject = null, array $properties = [], ?User $actor = null): void;
     }
     ```
   - `TabularExporter.php`:
     ```php
     namespace App\Contracts;

     use Symfony\Component\HttpFoundation\BinaryFileResponse;

     interface TabularExporter
     {
         public function download(object $exportInstance, string $fileName): BinaryFileResponse;
         public function store(object $exportInstance, string $filePath, string $disk = 'private'): bool;
     }
     ```
2. **Implementations (`app/Services/`)**:
   - `app/Services/Audit/SpatieAuditRecorder.php`:
     - Uses Spatie ActivityLog to log description, performedOn subject, causedBy actor, with custom properties.
   - `app/Services/Export/MaatwebsiteTabularExporter.php`:
     - Wraps `Maatwebsite\Excel\Facades\Excel` `download()` and `store()` methods.
3. **Exports (`app/Exports/`)**:
   - `app/Exports/AssetsExport.php`:
     - Implements `FromQuery` or `FromCollection`, `WithHeadings`, `WithMapping`.
     - Maps asset tag, name, type, status, serial number, manufacturer name, location name, official assignee, warranty expiry, AMC expiry.
   - `app/Exports/AgreementsExport.php`:
     - Maps agreement name, agency, type, annual cost, currency, expiry date, billing interval, paid till date.
4. **Service Provider**:
   - Bind `AuditRecorder` $\rightarrow$ `SpatieAuditRecorder` and `TabularExporter` $\rightarrow$ `MaatwebsiteTabularExporter` in `app/Providers/AppServiceProvider.php`.
5. **Tests**:
   - `tests/Unit/AuditAndExportTest.php`:
     - Test `AuditRecorder::record()` writes expected record in `activity_log` table with causer and properties.
     - Test `AssetsExport` and `AgreementsExport` headings and row mapping.
     - Test `TabularExporter::download()` triggers valid response.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-8-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
