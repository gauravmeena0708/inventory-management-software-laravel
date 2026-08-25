<?php

namespace Tests\Feature\Organization;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Models\OrganizationalUnit;
use App\Enums\UserRole;
use Database\Seeders\EpfoNdcHierarchySeeder;
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
}
