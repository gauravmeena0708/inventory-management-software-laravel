<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\EpfoHierarchyDemoSeeder;
use Database\Seeders\EpfoNdcHierarchySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EpfoHierarchyDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_realistic_idempotent_multi_level_demo_hierarchy(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@inventory.local']);
        $this->seed(EpfoNdcHierarchySeeder::class);

        $this->seed(EpfoHierarchyDemoSeeder::class);
        $firstCounts = [
            OrganizationalUnit::count(),
            Site::count(),
            Location::count(),
        ];
        $this->seed(EpfoHierarchyDemoSeeder::class);

        $this->assertSame($firstCounts, [
            OrganizationalUnit::count(),
            Site::count(),
            Location::count(),
        ]);

        $headOffice = OrganizationalUnit::where('code', 'HO-EPFO-DEMO')->firstOrFail();
        $zone = OrganizationalUnit::where('code', 'ZO-NORTH')->firstOrFail();
        $region = OrganizationalUnit::where('code', 'RO-DELHI')->firstOrFail();
        $district = OrganizationalUnit::where('code', 'DO-GURUGRAM')->firstOrFail();

        $this->assertSame(OrganizationalUnitType::HEAD_OFFICE, $headOffice->unit_type);
        $this->assertSame($headOffice->id, $zone->parent_id);
        $this->assertSame($zone->id, $region->parent_id);
        $this->assertSame($region->id, $district->parent_id);
        $this->assertStringStartsWith($region->path, $district->path);
        $this->assertTrue($district->sites()->exists());
        $this->assertSame(3, $district->sites()->firstOrFail()->locations()->count());

        $root = OrganizationalUnit::where('code', 'EPFO')->firstOrFail();
        $this->assertSame($root->id, $admin->fresh()->default_organizational_unit_id);
        $this->assertDatabaseHas('organizational_unit_user', [
            'user_id' => $admin->id,
            'organizational_unit_id' => $root->id,
            'read_scope' => 'descendants',
            'write_scope' => 'descendants',
        ]);

        $regionalOfficer = User::where('email', 'delhi.regional.officer@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('regional-demo-password', $regionalOfficer->password));
        $this->assertSame($region->id, $regionalOfficer->default_organizational_unit_id);
        $this->assertDatabaseHas('organizational_unit_user', [
            'user_id' => $regionalOfficer->id,
            'organizational_unit_id' => $region->id,
            'read_scope' => 'descendants',
            'write_scope' => 'local',
        ]);
    }
}
