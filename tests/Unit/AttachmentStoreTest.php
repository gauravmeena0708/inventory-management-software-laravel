<?php

namespace Tests\Unit;

use App\Contracts\AttachmentStore;
use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\File;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\User;
use App\Services\Storage\PrivateDiskAttachmentStore;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Support\LogOptions;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AttachmentStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AttachmentStore interface is bound to PrivateDiskAttachmentStore in the container.
     */
    public function test_attachment_store_is_bound_in_container(): void
    {
        $store = app(AttachmentStore::class);
        $this->assertInstanceOf(PrivateDiskAttachmentStore::class, $store);
    }

    /**
     * Test Location model configuration, fillable, activitylog, and relations.
     */
    public function test_location_model_configuration_and_relations(): void
    {
        $location = new Location([
            'name' => 'Main Server Room',
            'sublocation' => 'Zone A',
            'building' => 'HQ',
            'floor' => '2',
            'description' => 'Primary data center location',
        ]);

        $this->assertSame('Main Server Room', $location->name);
        $this->assertSame('Zone A', $location->sublocation);
        $this->assertSame('HQ', $location->building);
        $this->assertSame('2', $location->floor);
        $this->assertSame('Primary data center location', $location->description);

        $options = $location->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);

        $this->assertInstanceOf(HasMany::class, $location->officials());
        $this->assertInstanceOf(MorphMany::class, $location->attachments());
    }

    /**
     * Test Manufacturer model configuration, fillable, activitylog, and relations.
     */
    public function test_manufacturer_model_configuration_and_relations(): void
    {
        $manufacturer = new Manufacturer([
            'name' => 'Dell Technologies',
            'support_contact' => '+1-800-DELL',
            'website' => 'https://dell.com',
            'remarks' => 'Primary hardware vendor',
        ]);

        $this->assertSame('Dell Technologies', $manufacturer->name);
        $this->assertSame('+1-800-DELL', $manufacturer->support_contact);
        $this->assertSame('https://dell.com', $manufacturer->website);
        $this->assertSame('Primary hardware vendor', $manufacturer->remarks);

        $options = $manufacturer->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);

        $this->assertInstanceOf(MorphMany::class, $manufacturer->attachments());
    }

    /**
     * Test FileRecord model configuration, fillable, activitylog, and compatibility with File model.
     */
    public function test_file_record_and_file_model_configuration(): void
    {
        $fileRecord = new FileRecord([
            'name' => 'INV-2026-001',
            'efile_number' => 'EF-9876',
            'physical_name' => 'Rack 3',
            'physical_number' => 'P-45',
            'subject' => 'Procurement records',
            'division' => 'Infrastructure',
            'opened_at' => '2026-01-15',
            'legacy_id' => 101,
        ]);

        $this->assertSame('files', $fileRecord->getTable());
        $this->assertSame('INV-2026-001', $fileRecord->name);
        $this->assertSame('EF-9876', $fileRecord->efile_number);
        $this->assertSame('Rack 3', $fileRecord->physical_name);
        $this->assertSame('P-45', $fileRecord->physical_number);
        $this->assertSame('Procurement records', $fileRecord->subject);
        $this->assertSame('Infrastructure', $fileRecord->division);
        $this->assertSame(101, $fileRecord->legacy_id);

        $options = $fileRecord->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);

        $this->assertInstanceOf(MorphMany::class, $fileRecord->attachments());

        // File alias model check
        $legacyFile = new File([
            'name' => 'LEGACY-001',
            'efile' => 123,
            'physical' => 'Cabinet A',
        ]);
        $this->assertInstanceOf(FileRecord::class, $legacyFile);
        $this->assertSame('files', $legacyFile->getTable());
    }

    /**
     * Test Attachment model configuration, fillable, activitylog, and morph relations.
     */
    public function test_attachment_model_configuration_and_relations(): void
    {
        $attachment = new Attachment([
            'attachable_type' => FileRecord::class,
            'attachable_id' => 1,
            'disk' => 'private',
            'path' => 'attachments/2026/08/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'checksum' => hash('sha256', 'sample payload'),
            'uploaded_by' => 5,
        ]);

        $this->assertSame('attachments', $attachment->getTable());
        $this->assertSame(FileRecord::class, $attachment->attachable_type);
        $this->assertSame(1, $attachment->attachable_id);
        $this->assertSame('private', $attachment->disk);
        $this->assertSame('sample.pdf', $attachment->original_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertSame(2048, $attachment->size);
        $this->assertSame(5, $attachment->uploaded_by);

        $options = $attachment->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);

        $this->assertInstanceOf(MorphTo::class, $attachment->attachable());
        $this->assertInstanceOf(BelongsTo::class, $attachment->uploadedBy());
    }

    /**
     * Test PrivateDiskAttachmentStore stores file to private disk and calculates SHA256 checksum.
     */
    public function test_private_disk_attachment_store_stores_file(): void
    {
        Storage::fake('private');

        $user = User::factory()->create([
            'name' => 'Store Admin',
            'email' => 'admin@test.local',
            'role' => UserRole::ADMIN,
        ]);

        $fileRecord = FileRecord::factory()->create([
            'name' => 'FILE-100',
            'subject' => 'Storage Test',
        ]);

        $content = 'Test PDF payload content for SHA-256 calculation';
        $uploadedFile = UploadedFile::fake()->createWithContent('contract.pdf', $content);

        $store = new PrivateDiskAttachmentStore;
        $attachment = $store->store($uploadedFile, $fileRecord, $user, 'private');

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertSame('private', $attachment->disk);
        $this->assertSame('contract.pdf', $attachment->original_name);
        $this->assertSame(hash('sha256', $content), $attachment->checksum);
        $this->assertSame(FileRecord::class, $attachment->attachable_type);
        $this->assertSame($fileRecord->id, $attachment->attachable_id);
        $this->assertSame($user->id, $attachment->uploaded_by);

        Storage::disk('private')->assertExists($attachment->path);
    }

    /**
     * Test PrivateDiskAttachmentStore retrieves stored attachment as StreamedResponse.
     */
    public function test_private_disk_attachment_store_retrieves_file(): void
    {
        Storage::fake('private');

        $content = 'Sample content for streaming download';
        $path = 'attachments/2026/08/stream_test.txt';
        Storage::disk('private')->put($path, $content);

        $attachment = new Attachment([
            'attachable_type' => FileRecord::class,
            'attachable_id' => 1,
            'disk' => 'private',
            'path' => $path,
            'original_name' => 'stream_test.txt',
            'mime_type' => 'text/plain',
            'size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'uploaded_by' => 1,
        ]);

        $store = new PrivateDiskAttachmentStore;
        $response = $store->retrieve($attachment);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test PrivateDiskAttachmentStore deletes file from disk.
     */
    public function test_private_disk_attachment_store_deletes_file(): void
    {
        Storage::fake('private');

        $path = 'attachments/2026/08/delete_me.txt';
        Storage::disk('private')->put($path, 'File content to delete');
        Storage::disk('private')->assertExists($path);

        $attachment = new Attachment([
            'attachable_type' => FileRecord::class,
            'attachable_id' => 1,
            'disk' => 'private',
            'path' => $path,
            'original_name' => 'delete_me.txt',
            'mime_type' => 'text/plain',
            'size' => 20,
            'checksum' => hash('sha256', 'File content to delete'),
            'uploaded_by' => 1,
        ]);

        $store = new PrivateDiskAttachmentStore;
        $result = $store->delete($attachment);

        $this->assertTrue($result);
        Storage::disk('private')->assertMissing($path);
    }
}
