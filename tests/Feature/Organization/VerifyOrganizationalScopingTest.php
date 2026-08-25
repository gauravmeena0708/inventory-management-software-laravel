<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Site;
use App\Models\User;
use App\Services\Organization\OrganizationalHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VerifyOrganizationalScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifier_fails_closed_for_unmapped_active_records(): void
    {
        User::factory()->create(['default_organizational_unit_id' => null]);
        Location::factory()->create(['site_id' => null, 'is_active' => true]);
        Asset::factory()->create(['organizational_unit_id' => null]);

        $exitCode = Artisan::call('epfo:verify-organizational-scoping', ['--json' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($result['ok']);
        $this->assertSame(1, $result['issues']['active_assets_without_owner']);
        $this->assertSame(1, $result['issues']['active_locations_without_site']);
        $this->assertSame(1, $result['issues']['users_without_active_membership']);
    }

    public function test_verifier_passes_when_active_records_are_mapped(): void
    {
        $unit = app(OrganizationalHierarchyService::class)->createUnit([
            'code' => 'EPFO',
            'name' => 'Employees Provident Fund Organisation',
            'unit_type' => OrganizationalUnitType::ROOT,
        ]);
        $site = Site::create([
            'code' => 'NDC-HQ',
            'name' => 'National Data Centre',
            'is_active' => true,
        ]);
        $site->organizationalUnits()->attach($unit->id);
        $location = Location::factory()->create([
            'site_id' => $site->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'default_organizational_unit_id' => $unit->id,
        ]);
        $user->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);
        Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $location->id,
        ]);

        $exitCode = Artisan::call('epfo:verify-organizational-scoping', ['--json' => true]);
        $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['total_issues']);
    }
}
