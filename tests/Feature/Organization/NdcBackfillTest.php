<?php

namespace Tests\Feature\Organization;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\EpfoNdcHierarchySeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NdcBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the seeder runs
        $this->seed(EpfoNdcHierarchySeeder::class);
    }

    public function test_command_maps_locations_assets_and_users()
    {
        $location = Location::factory()->create(['site_id' => null, 'parent_id' => null]);
        $asset = Asset::factory()->create(['organizational_unit_id' => null]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER->value]);
        $manager = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER->value]);

        $this->artisan('epfo:map-ndc-inventory')
            ->assertSuccessful();

        // Assert Location
        $ndcHqSite = Site::where('code', 'NDC_HQ')->first();
        $this->assertEquals($ndcHqSite->id, $location->fresh()->site_id);

        // Assert Asset
        $ndcUnit = OrganizationalUnit::where('code', 'NDC')->first();
        $this->assertEquals($ndcUnit->id, $asset->fresh()->organizational_unit_id);

        // Assert Users
        $adminPivot = DB::table('organizational_unit_user')
            ->where('user_id', $admin->id)
            ->where('organizational_unit_id', $ndcUnit->id)
            ->first();
        $this->assertNotNull($adminPivot);
        $this->assertEquals('descendants', $adminPivot->read_scope);
        $this->assertEquals('descendants', $adminPivot->write_scope);
        $this->assertEquals($ndcUnit->id, $admin->fresh()->default_organizational_unit_id);

        $viewerPivot = DB::table('organizational_unit_user')
            ->where('user_id', $viewer->id)
            ->where('organizational_unit_id', $ndcUnit->id)
            ->first();
        $this->assertNotNull($viewerPivot);
        $this->assertEquals('descendants', $viewerPivot->read_scope);
        $this->assertEquals('none', $viewerPivot->write_scope);
        $this->assertEquals($ndcUnit->id, $viewer->fresh()->default_organizational_unit_id);

        $managerPivot = DB::table('organizational_unit_user')
            ->where('user_id', $manager->id)
            ->where('organizational_unit_id', $ndcUnit->id)
            ->first();
        $this->assertSame('local', $managerPivot->write_scope);
    }

    public function test_command_is_idempotent()
    {
        $location = Location::factory()->create(['site_id' => null, 'parent_id' => null]);
        $asset = Asset::factory()->create(['organizational_unit_id' => null]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->artisan('epfo:map-ndc-inventory')->assertSuccessful();
        $this->artisan('epfo:map-ndc-inventory')->assertSuccessful();

        $ndcUnit = OrganizationalUnit::where('code', 'NDC')->first();

        // Check pivot table count is 1 for the admin
        $adminPivotCount = DB::table('organizational_unit_user')
            ->where('user_id', $admin->id)
            ->where('organizational_unit_id', $ndcUnit->id)
            ->count();

        $this->assertEquals(1, $adminPivotCount);
    }

    public function test_ndc_hierarchy_seeder_is_idempotent_and_uses_stable_codes(): void
    {
        $this->seed(EpfoNdcHierarchySeeder::class);
        $this->seed(EpfoNdcHierarchySeeder::class);

        $this->assertSame(1, OrganizationalUnit::where('code', 'EPFO')->count());
        $this->assertSame(1, OrganizationalUnit::where('code', 'NDC')->count());
        $this->assertSame(1, Site::where('code', 'NDC_HQ')->count());

        $ndc = OrganizationalUnit::where('code', 'NDC')->firstOrFail();
        $this->assertSame('EPFO', $ndc->parent->code);
        $this->assertSame(1, $ndc->sites()->where('sites.code', 'NDC_HQ')->count());
    }

    public function test_dry_run_does_not_commit_changes()
    {
        $location = Location::factory()->create(['site_id' => null, 'parent_id' => null]);
        $asset = Asset::factory()->create(['organizational_unit_id' => null]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->artisan('epfo:map-ndc-inventory', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertNull($location->fresh()->site_id);
        $this->assertNull($asset->fresh()->organizational_unit_id);
        $this->assertNull($admin->fresh()->default_organizational_unit_id);

        $ndcUnit = OrganizationalUnit::where('code', 'NDC')->first();
        $adminPivot = DB::table('organizational_unit_user')
            ->where('user_id', $admin->id)
            ->where('organizational_unit_id', $ndcUnit->id)
            ->first();

        $this->assertNull($adminPivot);
    }

    public function test_verify_mode_is_read_only_and_fails_until_reconciliation_is_clean(): void
    {
        $location = Location::factory()->create([
            'site_id' => null,
            'parent_id' => null,
            'path' => null,
            'building' => 'Legacy Building',
        ]);
        $asset = Asset::factory()->create(['organizational_unit_id' => null]);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER->value]);

        $this->artisan('epfo:map-ndc-inventory', ['--verify' => true])
            ->expectsOutputToContain('Verification failed')
            ->assertExitCode(1);

        $this->assertNull($location->fresh()->site_id);
        $this->assertNull($location->fresh()->path);
        $this->assertNull($asset->fresh()->organizational_unit_id);
        $this->assertNull($viewer->fresh()->default_organizational_unit_id);

        $this->artisan('epfo:map-ndc-inventory', ['--apply' => true])->assertSuccessful();
        $this->artisan('epfo:map-ndc-inventory', ['--verify' => true])
            ->expectsOutputToContain('Verification passed')
            ->assertSuccessful();

        $this->assertSame('Legacy Building', $location->fresh()->building);
        $this->assertSame('/'.$location->id.'/', $location->fresh()->path);
    }

    public function test_resume_only_processes_unresolved_or_incorrect_records(): void
    {
        $ndcUnit = OrganizationalUnit::where('code', 'NDC')->firstOrFail();
        $ndcSite = Site::where('code', 'NDC_HQ')->firstOrFail();
        $completeAdmin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'default_organizational_unit_id' => $ndcUnit->id,
        ]);
        DB::table('organizational_unit_user')->insert([
            'organizational_unit_id' => $ndcUnit->id,
            'user_id' => $completeAdmin->id,
            'read_scope' => 'descendants',
            'write_scope' => 'descendants',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $originalUpdatedAt = DB::table('organizational_unit_user')
            ->where('user_id', $completeAdmin->id)
            ->value('updated_at');

        $unmappedLocation = Location::factory()->create(['site_id' => null, 'path' => null]);
        $unmappedAsset = Asset::factory()->create(['organizational_unit_id' => null]);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER->value]);

        $this->artisan('epfo:map-ndc-inventory', ['--resume' => true])
            ->expectsOutputToContain('RESUME mode')
            ->assertSuccessful();

        $this->assertEquals($ndcSite->id, $unmappedLocation->fresh()->site_id);
        $this->assertEquals($ndcUnit->id, $unmappedAsset->fresh()->organizational_unit_id);
        $this->assertEquals($ndcUnit->id, $viewer->fresh()->default_organizational_unit_id);
        $this->assertEquals(
            $originalUpdatedAt,
            DB::table('organizational_unit_user')->where('user_id', $completeAdmin->id)->value('updated_at')
        );
    }

    public function test_reconciliation_reports_invalid_paths_and_repairs_repairable_paths(): void
    {
        $site = Site::where('code', 'NDC_HQ')->firstOrFail();
        $parent = Location::factory()->create(['site_id' => $site->id, 'path' => '/invalid/']);
        $child = Location::factory()->create([
            'site_id' => $site->id,
            'parent_id' => $parent->id,
            'path' => '/also-invalid/',
        ]);

        $this->artisan('epfo:map-ndc-inventory', ['--verify' => true])->assertExitCode(1);
        $this->artisan('epfo:map-ndc-inventory', ['--resume' => true])->assertSuccessful();

        $this->assertSame('/'.$parent->id.'/', $parent->fresh()->path);
        $this->assertSame('/'.$parent->id.'/'.$child->id.'/', $child->fresh()->path);
    }

    public function test_ndc_mapping_never_queries_the_legacy_connection(): void
    {
        $connections = [];
        DB::listen(function (QueryExecuted $query) use (&$connections): void {
            $connections[] = $query->connectionName;
        });

        Location::factory()->create(['site_id' => null]);
        Asset::factory()->create(['organizational_unit_id' => null]);
        User::factory()->create();

        $this->artisan('epfo:map-ndc-inventory', ['--dry-run' => true])->assertSuccessful();

        $this->assertNotContains('legacy', $connections);
    }
}
