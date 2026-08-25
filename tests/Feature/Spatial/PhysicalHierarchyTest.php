<?php

namespace Tests\Feature\Spatial;

use App\Enums\LocationType;
use App\Exceptions\InvalidHierarchyMove;
use App\Models\Location;
use App\Models\Site;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected PhysicalHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhysicalHierarchyService();
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

        $this->assertEquals('/' . $root->id . '/', $root->path);

        $child = $this->service->createLocation([
            'name' => 'Floor 1',
            'floor' => '1',
            'is_active' => true,
            'parent_id' => $root->id,
            'location_type' => LocationType::FLOOR,
        ]);

        $this->assertEquals('/' . $root->id . '/' . $child->id . '/', $child->path);
    }

    public function test_service_moves_subtree_and_updates_paths()
    {
        $root1 = $this->service->createLocation(['name' => 'Root 1', 'floor' => '1', 'is_active' => true]);
        $root2 = $this->service->createLocation(['name' => 'Root 2', 'floor' => '1', 'is_active' => true]);
        
        $child1 = $this->service->createLocation(['name' => 'Child 1', 'floor' => '1', 'is_active' => true, 'parent_id' => $root1->id]);
        $grandchild = $this->service->createLocation(['name' => 'Grandchild', 'floor' => '1', 'is_active' => true, 'parent_id' => $child1->id]);

        $this->assertEquals('/' . $root1->id . '/' . $child1->id . '/' . $grandchild->id . '/', $grandchild->path);

        $this->service->moveLocation($child1, $root2);

        $child1->refresh();
        $grandchild->refresh();

        $this->assertEquals($root2->id, $child1->parent_id);
        $this->assertEquals('/' . $root2->id . '/' . $child1->id . '/', $child1->path);
        $this->assertEquals('/' . $root2->id . '/' . $child1->id . '/' . $grandchild->id . '/', $grandchild->path);
    }

    public function test_service_prevents_cycles()
    {
        $root = $this->service->createLocation(['name' => 'Root', 'floor' => '1', 'is_active' => true]);
        $child = $this->service->createLocation(['name' => 'Child', 'floor' => '1', 'is_active' => true, 'parent_id' => $root->id]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move a location to its own descendant.');

        $this->service->moveLocation($root, $child);
    }
}
