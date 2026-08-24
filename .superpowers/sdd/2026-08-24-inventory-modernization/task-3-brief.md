# Task 3 Brief: Master Data, Locations, Manufacturers & File Attachments

## Objective
Implement master data models (`Location`, `Manufacturer`, `FileRecord`), the binary `Attachment` model, the `AttachmentStore` interface and `PrivateDiskAttachmentStore` implementation, create anonymous migrations, update models to modern ActivityLog v4/v5 options, and write unit tests.

## Specific Requirements
1. **Migrations**:
   - `database/migrations/2026_08_24_000002_create_master_data_and_attachments_tables.php`:
     - `locations`: `id`, `name`, `sublocation` (nullable), `building` (nullable), `floor` (nullable), `description` (nullable), `timestamps`, `deleted_at`.
     - `manufacturers`: `id`, `name`, `support_contact` (nullable), `website` (nullable), `remarks` (nullable), `timestamps`, `deleted_at`.
     - `files` / `file_records`: `id`, `name`, `efile_number` (nullable), `physical_name` (nullable), `physical_number` (nullable), `subject` (nullable), `division` (nullable), `opened_at` (nullable), `legacy_id` (nullable, unique), `timestamps`, `deleted_at`.
     - `attachments`: `id`, `attachable_type`, `attachable_id`, `disk` (default 'private'), `path`, `original_name`, `mime_type`, `size` (unsignedBigInteger), `checksum` (char 64, sha256), `uploaded_by` (foreignId to users), `timestamps`, `deleted_at`. Indexes on `(attachable_type, attachable_id)` and `uploaded_by`.
2. **Models**:
   - `app/Models/Location.php`: `$fillable`, `ActivitylogOptions` (`LogOptions::defaults()->logFillable()->logOnlyDirty()`).
   - `app/Models/Manufacturer.php`: `$fillable`, `ActivitylogOptions`.
   - `app/Models/FileRecord.php` (and alias/compatibility `app/Models/File.php` extending `FileRecord` or referencing `files` table): `$fillable`, `hasMany(Attachment::class, 'attachable')`, `ActivitylogOptions`.
   - `app/Models/Attachment.php`: `$fillable`, `morphTo(attachable)`, `belongsTo(User::class, 'uploaded_by')`.
3. **Attachment Storage Contract & Implementation**:
   - `app/Contracts/AttachmentStore.php`:
     ```php
     interface AttachmentStore {
         public function store(UploadedFile $file, Model $attachable, User $user, ?string $disk = 'private'): Attachment;
         public function retrieve(Attachment $attachment): StreamedResponse;
         public function delete(Attachment $attachment): bool;
     }
     ```
   - `app/Services/Storage/PrivateDiskAttachmentStore.php`:
     - Stores file to private disk with SHA-256 checksum and mime validation.
     - Registers binding in `app/Providers/AppServiceProvider.php`.
4. **Tests**:
   - `tests/Unit/AttachmentStoreTest.php`:
     - Test storing file calculates correct checksum and saves database attachment record.
     - Test private storage disk isolation and model relations.

## Output Report Contract
Write execution report to:
`.superpowers/sdd/2026-08-24-inventory-modernization/task-3-report.md`
Return in chat only status (`DONE` / `BLOCKED`), list of touched files, and a one-line summary.
