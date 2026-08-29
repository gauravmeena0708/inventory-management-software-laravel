<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Consumable;
use App\Models\Devcat;
use App\Models\Developer;
use App\Models\Entry;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Official;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Support\LogOptions;
use Tests\TestCase;

class PersonnelEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test developer creation and transparent attribute decryption.
     */
    public function test_developer_creation_and_transparent_decryption(): void
    {
        $category = Devcat::factory()->create(['name' => 'Backend Architect']);
        $manager = User::factory()->create(['name' => 'Tech Lead Jane']);

        $developer = Developer::create([
            'name' => 'Rohit Sharma',
            'reporting_id' => $manager->id,
            'category_id' => $category->id,
            'phone' => '+91-9876543210',
            'email' => 'rohit.sharma@example.gov.in',
            'salary' => '125000',
            'status' => 'active',
            'remarks' => 'Senior Laravel Engineer',
        ]);

        $this->assertSame('Rohit Sharma', $developer->name);
        $this->assertSame('+91-9876543210', $developer->phone);
        $this->assertSame('rohit.sharma@example.gov.in', $developer->email);
        $this->assertSame('125000', $developer->salary);
        $this->assertSame('active', $developer->status);

        // Verify fresh reloaded model instance from database
        $fresh = $developer->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame('+91-9876543210', $fresh->phone);
        $this->assertSame('rohit.sharma@example.gov.in', $fresh->email);
        $this->assertSame('125000', $fresh->salary);
    }

    /**
     * Test raw database row contains AES ciphertext rather than plain text.
     */
    public function test_developer_raw_database_contains_ciphertext_not_plaintext(): void
    {
        $plainPhone = '+91-9988776655';
        $plainEmail = 'confidential.dev@domain.org';
        $plainSalary = '180000';

        $developer = Developer::factory()->create([
            'name' => 'Priya Patel',
            'phone' => $plainPhone,
            'email' => $plainEmail,
            'salary' => $plainSalary,
        ]);

        // Direct raw DB query bypassing Eloquent model casts
        $raw = DB::table('developers')->where('id', $developer->id)->first();
        $this->assertNotNull($raw);

        // Must NOT match plaintext strings
        $this->assertNotSame($plainPhone, $raw->phone);
        $this->assertNotSame($plainEmail, $raw->email);
        $this->assertNotSame($plainSalary, $raw->salary);

        // Ciphertext should not even contain the plain strings
        $this->assertStringNotContainsString($plainPhone, $raw->phone);
        $this->assertStringNotContainsString($plainEmail, $raw->email);
        $this->assertStringNotContainsString($plainSalary, $raw->salary);

        // Decrypting raw ciphertext manually via Crypt facade yields exact plaintext values
        $this->assertSame($plainPhone, Crypt::decryptString($raw->phone));
        $this->assertSame($plainEmail, Crypt::decryptString($raw->email));
        $this->assertSame($plainSalary, Crypt::decryptString($raw->salary));
    }

    /**
     * Test nullable handling for encrypted developer attributes.
     */
    public function test_developer_nullable_encrypted_fields(): void
    {
        $developer = Developer::create([
            'name' => 'Anonymous Contributor',
            'phone' => null,
            'email' => null,
            'salary' => null,
            'status' => 'active',
        ]);

        $raw = DB::table('developers')->where('id', $developer->id)->first();
        $this->assertNotNull($raw);
        $this->assertNull($raw->phone);
        $this->assertNull($raw->email);
        $this->assertNull($raw->salary);

        $fresh = $developer->fresh();
        $this->assertNull($fresh->phone);
        $this->assertNull($fresh->email);
        $this->assertNull($fresh->salary);
    }

    /**
     * Test updating encrypted attributes and verifying new ciphertext in database.
     */
    public function test_developer_updating_encrypted_attributes(): void
    {
        $developer = Developer::factory()->create([
            'salary' => '75000',
            'phone' => '+91-1111111111',
            'email' => 'old.email@domain.com',
        ]);

        $developer->update([
            'salary' => '90000',
            'phone' => '+91-2222222222',
            'email' => 'new.email@domain.com',
        ]);

        $fresh = $developer->fresh();
        $this->assertSame('90000', $fresh->salary);
        $this->assertSame('+91-2222222222', $fresh->phone);
        $this->assertSame('new.email@domain.com', $fresh->email);

        $raw = DB::table('developers')->where('id', $developer->id)->first();
        $this->assertSame('90000', Crypt::decryptString($raw->salary));
        $this->assertSame('+91-2222222222', Crypt::decryptString($raw->phone));
        $this->assertSame('new.email@domain.com', Crypt::decryptString($raw->email));
    }

    /**
     * Test Developer scopes: active and discontinued.
     */
    public function test_developer_scopes_active_and_discontinued(): void
    {
        Developer::factory()->active()->create(['name' => 'Active Dev 1']);
        Developer::factory()->active()->create(['name' => 'Active Dev 2']);
        Developer::factory()->discontinued()->create(['name' => 'Former Dev 1']);

        $active = Developer::active()->get();
        $discontinued = Developer::discontinued()->get();

        $this->assertCount(2, $active);
        $this->assertTrue($active->contains('name', 'Active Dev 1'));
        $this->assertTrue($active->contains('name', 'Active Dev 2'));

        $this->assertCount(1, $discontinued);
        $this->assertTrue($discontinued->contains('name', 'Former Dev 1'));
    }

    /**
     * Test Developer relationships with User (reporting manager) and Devcat (category).
     */
    public function test_developer_relationships(): void
    {
        $manager = User::factory()->create(['name' => 'Engineering Manager']);
        $category = Devcat::factory()->create(['name' => 'Fullstack Specialist']);

        $developer = Developer::factory()
            ->withReportingUser($manager)
            ->withCategory($category)
            ->create();

        $this->assertInstanceOf(BelongsTo::class, $developer->reportingUser());
        $this->assertInstanceOf(BelongsTo::class, $developer->reporting());
        $this->assertInstanceOf(BelongsTo::class, $developer->category());

        $this->assertSame($manager->id, $developer->reportingUser->id);
        $this->assertSame($manager->name, $developer->reporting->name);
        $this->assertSame($category->id, $developer->category->id);
        $this->assertSame('Fullstack Specialist', $developer->category->name);
    }

    /**
     * Test Developer Spatie Activitylog options exclude encrypted sensitive fields from plain logging.
     */
    public function test_developer_activity_log_options_exclude_encrypted_fields(): void
    {
        $developer = new Developer();
        $options = $developer->getActivitylogOptions();

        $this->assertInstanceOf(LogOptions::class, $options);
        $this->assertTrue($options->logFillable);
        $this->assertTrue($options->logOnlyDirty);

        // Ensure sensitive fields are in logExceptAttributes
        $this->assertContains('salary', $options->logExceptAttributes);
        $this->assertContains('phone', $options->logExceptAttributes);
        $this->assertContains('email', $options->logExceptAttributes);
    }

    /**
     * Test Official model attributes, relations, and activity logging.
     */
    public function test_official_model_and_relations(): void
    {
        $location = Location::factory()->create(['name' => 'Headquarters Block A']);

        $official = Official::factory()->forLocation($location)->create([
            'name' => 'Dr. Arvind Sharma',
            'title' => 'Director',
            'designation' => 'Director General',
            'department' => 'Information Technology',
            'email' => 'arvind.sharma@gov.in',
            'phone' => '+91-11-23456789',
        ]);

        $this->assertSame('Dr. Arvind Sharma', $official->name);
        $this->assertSame('Director General', $official->designation);
        $this->assertSame('Information Technology', $official->department);

        // Test location relation
        $this->assertInstanceOf(BelongsTo::class, $official->location());
        $this->assertSame($location->id, $official->location->id);

        // Test assets relation
        $asset = Asset::factory()->create([
            'name' => 'Dell Latitude 7420',
            'assigned_official_id' => $official->id,
        ]);
        $this->assertInstanceOf(HasMany::class, $official->assets());
        $this->assertInstanceOf(HasMany::class, $official->assignedAssets());
        $this->assertTrue($official->assets->contains('id', $asset->id));
        $this->assertTrue($official->assignedAssets->contains('id', $asset->id));

        // Test assignments relation
        $assignment = AssetAssignment::factory()->create([
            'asset_id' => $asset->id,
            'official_id' => $official->id,
        ]);
        $this->assertInstanceOf(HasMany::class, $official->assignments());
        $this->assertTrue($official->assignments->contains('id', $assignment->id));

        // Test entries relation
        $consumable = Consumable::factory()->create(['in_stock' => 100]);
        $entry = Entry::create([
            'consumable_id' => $consumable->id,
            'type' => 'issue',
            'quantity' => 5,
            'stock_after' => 95,
            'recipient_official_id' => $official->id,
        ]);
        $this->assertInstanceOf(HasMany::class, $official->entries());
        $this->assertTrue($official->entries->contains('id', $entry->id));

        // Test ActivityLog options
        $options = $official->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
        $this->assertTrue($options->logFillable);
        $this->assertTrue($options->logOnlyDirty);
    }

    /**
     * Test Devcat model casts, relations, and activity logging.
     */
    public function test_devcat_model_casts_and_relations(): void
    {
        $devcat = Devcat::factory()->create([
            'name' => 'Lead DevOps Engineer',
            'salary' => '₹1,50,000+',
            'exp' => '8+ Years',
            'dev' => '5',
            'collab' => '10',
            'qualification' => 'M.Tech / B.Tech Computer Science',
        ]);

        $this->assertSame(5, $devcat->dev);
        $this->assertSame(10, $devcat->collab);
        $this->assertIsInt($devcat->dev);
        $this->assertIsInt($devcat->collab);

        // Test developers relation
        $dev1 = Developer::factory()->create(['category_id' => $devcat->id]);
        $dev2 = Developer::factory()->create(['category_id' => $devcat->id]);

        $this->assertInstanceOf(HasMany::class, $devcat->developers());
        $this->assertCount(2, $devcat->developers);
        $this->assertTrue($devcat->developers->contains('id', $dev1->id));
        $this->assertTrue($devcat->developers->contains('id', $dev2->id));

        // Test ActivityLog options
        $options = $devcat->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
        $this->assertTrue($options->logFillable);
        $this->assertTrue($options->logOnlyDirty);
    }

    /**
     * Test Task model casts, scopes, relations, and activity logging.
     */
    public function test_task_model_casts_scopes_and_relations(): void
    {
        $user = User::factory()->create(['name' => 'Support Officer']);
        $file = FileRecord::factory()->create(['name' => 'Server Migration Plan']);

        $task = Task::factory()
            ->withAssignedUser($user)
            ->withFile($file)
            ->create([
                'title' => 'Migrate Auth Database to MySQL 8',
                'description' => 'Perform staging rehearsal followed by production migration.',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => '2026-09-15',
                'remarks' => 'Maintenance window scheduled at 10 PM',
            ]);

        // Casts verification
        $this->assertInstanceOf(DateTimeInterface::class, $task->due_date);
        $this->assertSame('2026-09-15', $task->due_date->format('Y-m-d'));

        // Relations verification
        $this->assertInstanceOf(BelongsTo::class, $task->assignedUser());
        $this->assertInstanceOf(BelongsTo::class, $task->assignedTo());
        $this->assertInstanceOf(BelongsTo::class, $task->file());

        $this->assertSame($user->id, $task->assignedUser->id);
        $this->assertSame($user->name, $task->assignedTo->name);
        $this->assertSame($file->id, $task->file->id);
        $this->assertSame('Server Migration Plan', $task->file->name);

        // Scopes verification
        $completedTask = Task::factory()->completed()->create(['title' => 'Configure NGINX SSL']);

        $pendingTasks = Task::pending()->get();
        $completedTasks = Task::completed()->get();

        $this->assertTrue($pendingTasks->contains('id', $task->id));
        $this->assertFalse($pendingTasks->contains('id', $completedTask->id));

        $this->assertTrue($completedTasks->contains('id', $completedTask->id));
        $this->assertFalse($completedTasks->contains('id', $task->id));

        // ActivityLog options
        $options = $task->getActivitylogOptions();
        $this->assertInstanceOf(LogOptions::class, $options);
        $this->assertTrue($options->logFillable);
        $this->assertTrue($options->logOnlyDirty);
    }

    /**
     * Test soft deletes across Official, Devcat, Developer, and Task models.
     */
    public function test_soft_deletes_on_personnel_and_task_models(): void
    {
        $official = Official::factory()->create();
        $devcat = Devcat::factory()->create();
        $developer = Developer::factory()->create();
        $task = Task::factory()->create();

        $official->delete();
        $devcat->delete();
        $developer->delete();
        $task->delete();

        $this->assertTrue($official->trashed());
        $this->assertTrue($devcat->trashed());
        $this->assertTrue($developer->trashed());
        $this->assertTrue($task->trashed());

        $this->assertNull(Official::find($official->id));
        $this->assertNull(Devcat::find($devcat->id));
        $this->assertNull(Developer::find($developer->id));
        $this->assertNull(Task::find($task->id));

        $this->assertNotNull(Official::withTrashed()->find($official->id));
        $this->assertNotNull(Devcat::withTrashed()->find($devcat->id));
        $this->assertNotNull(Developer::withTrashed()->find($developer->id));
        $this->assertNotNull(Task::withTrashed()->find($task->id));
    }
}
