<?php

namespace Tests\Feature\Spatial;

use App\Enums\LocationType;
use App\Exceptions\InvalidHierarchyMove;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhysicalHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected PhysicalHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhysicalHierarchyService;
    }

    public function test_can_create_site()
    {
        $site = Site::create([
            'code' => 'HQ-01',
            'name' => 'Headquarters',
            'address' => '123 Main St',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('sites', [
            'code' => 'HQ-01',
            'name' => 'Headquarters',
        ]);
        $this->assertTrue($site->is_active);
    }

    public function test_can_create_location_with_new_schema()
    {
        $site = Site::create([
            'code' => 'HQ-01',
            'name' => 'Headquarters',
        ]);

        $location = Location::create([
            'name' => 'Server Room 1',
            'floor' => '1',
            'site_id' => $site->id,
            'location_type' => LocationType::ROOM,
            'is_restricted' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('locations', [
            'name' => 'Server Room 1',
            'floor' => '1',
            'site_id' => $site->id,
            'location_type' => 'ROOM',
            'is_restricted' => 1,
        ]);

        $this->assertTrue($location->is_restricted);
        $this->assertEquals(LocationType::ROOM, $location->location_type);
    }

    public function test_service_creates_location_and_generates_path()
    {
        $root = $this->service->createLocation([
            'name' => 'Building A',
            'floor' => '1',
            'is_active' => true,
            'location_type' => LocationType::BUILDING,
        ]);

        $this->assertEquals('/'.$root->id.'/', $root->path);

        $child = $this->service->createLocation([
            'name' => 'Floor 1',
            'floor' => '1',
            'is_active' => true,
            'parent_id' => $root->id,
            'location_type' => LocationType::FLOOR,
        ]);

        $this->assertEquals('/'.$root->id.'/'.$child->id.'/', $child->path);
    }

    public function test_service_moves_subtree_and_updates_paths()
    {
        $root1 = $this->service->createLocation(['name' => 'Root 1', 'floor' => '1', 'is_active' => true]);
        $root2 = $this->service->createLocation(['name' => 'Root 2', 'floor' => '1', 'is_active' => true]);

        $child1 = $this->service->createLocation(['name' => 'Child 1', 'floor' => '1', 'is_active' => true, 'parent_id' => $root1->id]);
        $grandchild = $this->service->createLocation(['name' => 'Grandchild', 'floor' => '1', 'is_active' => true, 'parent_id' => $child1->id]);

        $this->assertEquals('/'.$root1->id.'/'.$child1->id.'/'.$grandchild->id.'/', $grandchild->path);

        $this->service->moveLocation($child1, $root2);

        $child1->refresh();
        $grandchild->refresh();

        $this->assertEquals($root2->id, $child1->parent_id);
        $this->assertEquals('/'.$root2->id.'/'.$child1->id.'/', $child1->path);
        $this->assertEquals('/'.$root2->id.'/'.$child1->id.'/'.$grandchild->id.'/', $grandchild->path);
    }

    public function test_service_prevents_cycles()
    {
        $root = $this->service->createLocation(['name' => 'Root', 'floor' => '1', 'is_active' => true]);
        $child = $this->service->createLocation(['name' => 'Child', 'floor' => '1', 'is_active' => true, 'parent_id' => $root->id]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move a location to its own descendant.');

        $this->service->moveLocation($root, $child);
    }

    public function test_site_coordinates_are_validated(): void
    {
        $site = $this->service->createSite([
            'code' => 'NDC-VALID',
            'name' => 'NDC Valid Site',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'altitude' => 216.5,
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->assertSame('28.6139000', $site->latitude);
        $this->assertSame('77.2090000', $site->longitude);

        $this->expectException(ValidationException::class);

        $this->service->createSite([
            'code' => 'INVALID-GPS',
            'name' => 'Invalid GPS',
            'latitude' => 90.0000001,
            'longitude' => -180.0000001,
        ]);
    }

    public function test_local_coordinates_are_validated_and_preserved(): void
    {
        $building = $this->service->createLocation([
            'name' => 'Building A',
            'floor' => '0',
            'location_type' => LocationType::BUILDING,
            'local_x' => -10.25,
            'local_y' => 20.5,
            'local_z' => 0,
        ]);

        $this->assertSame('-10.25', $building->local_x);
        $this->assertSame('20.50', $building->local_y);
        $this->assertSame('0.00', $building->local_z);

        $this->expectException(ValidationException::class);

        $this->service->createLocation([
            'name' => 'Invalid coordinate',
            'floor' => '0',
            'location_type' => LocationType::BUILDING,
            'local_x' => 'east of origin',
        ]);
    }

    public function test_structured_location_parent_types_are_enforced(): void
    {
        $building = $this->service->createLocation([
            'name' => 'Building A',
            'floor' => '0',
            'location_type' => LocationType::BUILDING,
        ]);
        $floor = $this->service->createLocation([
            'name' => 'Floor 1',
            'floor' => '1',
            'location_type' => LocationType::FLOOR,
            'parent_id' => $building->id,
        ]);
        $store = $this->service->createLocation([
            'name' => 'Main Store',
            'floor' => '1',
            'location_type' => LocationType::STORE,
            'parent_id' => $floor->id,
        ]);
        $bin = $this->service->createLocation([
            'name' => 'Bin A',
            'floor' => '1',
            'location_type' => LocationType::BIN,
            'parent_id' => $store->id,
        ]);

        $this->assertSame($store->id, $bin->parent_id);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('BIN cannot be placed beneath FLOOR.');

        $this->service->moveLocation($bin, $floor);
    }

    public function test_structured_non_building_location_cannot_be_a_site_root(): void
    {
        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('FLOOR locations require a structured parent.');

        $this->service->createLocation([
            'name' => 'Orphan floor',
            'floor' => '1',
            'location_type' => LocationType::FLOOR,
        ]);
    }

    public function test_child_inherits_parent_site_and_rejects_a_different_site(): void
    {
        $site = Site::create(['code' => 'SITE-A', 'name' => 'Site A']);
        $otherSite = Site::create(['code' => 'SITE-B', 'name' => 'Site B']);
        $building = $this->service->createLocation([
            'name' => 'Building A',
            'floor' => '0',
            'site_id' => $site->id,
            'location_type' => LocationType::BUILDING,
        ]);
        $floor = $this->service->createLocation([
            'name' => 'Floor 1',
            'floor' => '1',
            'parent_id' => $building->id,
            'location_type' => LocationType::FLOOR,
        ]);

        $this->assertSame($site->id, $floor->site_id);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('same site');

        $this->service->createLocation([
            'name' => 'Floor 2',
            'floor' => '2',
            'site_id' => $otherSite->id,
            'parent_id' => $building->id,
            'location_type' => LocationType::FLOOR,
        ]);
    }

    public function test_site_root_locations_are_shared_by_all_units_associated_with_the_site(): void
    {
        $site = Site::create(['code' => 'SHARED', 'name' => 'Shared Campus']);
        $firstUnit = OrganizationalUnit::factory()->create();
        $secondUnit = OrganizationalUnit::factory()->create();
        $site->organizationalUnits()->attach([$firstUnit->id, $secondUnit->id]);

        $root = $this->service->createLocation([
            'name' => 'Shared Building',
            'floor' => '0',
            'site_id' => $site->id,
            'location_type' => LocationType::BUILDING,
        ]);

        $this->assertCount(2, $root->site->organizationalUnits);
        $this->assertFalse(Schema::hasColumn('locations', 'organizational_unit_id'));
    }

    public function test_legacy_location_fields_remain_crud_compatible(): void
    {
        $location = Location::create([
            'name' => 'Legacy Location',
            'sublocation' => 'Old server room',
            'building' => 'Main Block',
            'floor' => '3',
            'seat' => 'S-12',
            'point' => 'P-09',
            'pin' => '110066',
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'building' => 'Main Block',
            'seat' => 'S-12',
            'point' => 'P-09',
        ]);

        $location->update(['sublocation' => 'Renamed server room', 'floor' => '4']);
        $this->assertSame('Renamed server room', $location->fresh()->sublocation);

        $location->delete();
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }
}
