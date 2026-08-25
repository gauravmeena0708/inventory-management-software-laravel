<?php

namespace Tests\Feature\Organization;

use App\Exceptions\InvalidHierarchyMove;
use App\Models\OrganizationalUnit;
use App\Services\Organization\OrganizationalHierarchyService;
use App\Enums\OrganizationalUnitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class OrganizationalHierarchyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrganizationalHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrganizationalHierarchyService();
    }

    public function test_creates_root_unit_with_correct_path()
    {
        $unit = $this->service->createUnit([
            'name' => 'Root Unit',
            'unit_type' => OrganizationalUnitType::ROOT->value,
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $this->assertNull($unit->parent_id);
        $this->assertEquals("/{$unit->id}/", $unit->path);
        $this->assertDatabaseHas('organizational_units', [
            'id' => $unit->id,
            'path' => "/{$unit->id}/"
        ]);
    }

    public function test_creates_child_unit_with_correct_path()
    {
        $root = $this->service->createUnit([
            'name' => 'Root Unit',
            'unit_type' => OrganizationalUnitType::ROOT->value,
            'code' => 'ROOT',
            'is_active' => true,
        ]);

        $child = $this->service->createUnit([
            'name' => 'Child Unit',
            'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value,
            'code' => 'CHILD',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        $this->assertEquals($root->id, $child->parent_id);
        $this->assertEquals("/{$root->id}/{$child->id}/", $child->path);
    }

    public function test_moves_subtree_to_new_parent_and_updates_paths()
    {
        $root1 = $this->service->createUnit(['name' => 'Root 1', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R1', 'is_active' => true]);
        $root2 = $this->service->createUnit(['name' => 'Root 2', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R2', 'is_active' => true]);

        $child1 = $this->service->createUnit(['name' => 'Child 1', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'C1', 'parent_id' => $root1->id, 'is_active' => true]);
        $grandchild = $this->service->createUnit(['name' => 'Grandchild', 'unit_type' => OrganizationalUnitType::ZONAL_OFFICE->value, 'code' => 'GC', 'parent_id' => $child1->id, 'is_active' => true]);

        $this->assertEquals("/{$root1->id}/{$child1->id}/", $child1->path);
        $this->assertEquals("/{$root1->id}/{$child1->id}/{$grandchild->id}/", $grandchild->path);

        // Move child1 to root2
        $this->service->moveUnit($child1, $root2);

        $child1->refresh();
        $grandchild->refresh();

        $this->assertEquals($root2->id, $child1->parent_id);
        $this->assertEquals("/{$root2->id}/{$child1->id}/", $child1->path);
        $this->assertEquals("/{$root2->id}/{$child1->id}/{$grandchild->id}/", $grandchild->path);
    }

    public function test_rejects_moving_unit_to_itself()
    {
        $root = $this->service->createUnit(['name' => 'Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R', 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move unit to itself.');

        $this->service->moveUnit($root, $root);
    }

    public function test_rejects_moving_unit_to_descendant_cycle()
    {
        $root = $this->service->createUnit(['name' => 'Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R', 'is_active' => true]);
        $child = $this->service->createUnit(['name' => 'Child', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'C', 'parent_id' => $root->id, 'is_active' => true]);
        $grandchild = $this->service->createUnit(['name' => 'Grandchild', 'unit_type' => OrganizationalUnitType::ZONAL_OFFICE->value, 'code' => 'GC', 'parent_id' => $child->id, 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move a unit to its own descendant.');

        $this->service->moveUnit($root, $grandchild);
    }

    public function test_rejects_moving_to_inactive_parent()
    {
        $activeRoot = $this->service->createUnit(['name' => 'Root 1', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R1', 'is_active' => true]);
        $inactiveRoot = $this->service->createUnit(['name' => 'Root 2', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R2', 'is_active' => false]);
        
        $child = $this->service->createUnit(['name' => 'Child', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'C', 'parent_id' => $activeRoot->id, 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move unit to an inactive parent.');

        $this->service->moveUnit($child, $inactiveRoot);
    }

    public function test_rejects_moving_to_deleted_parent()
    {
        $activeRoot = $this->service->createUnit(['name' => 'Root 1', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R1', 'is_active' => true]);
        $deletedRoot = $this->service->createUnit(['name' => 'Root 2', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R2', 'is_active' => true]);
        $deletedRoot->delete();
        
        $child = $this->service->createUnit(['name' => 'Child', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'C', 'parent_id' => $activeRoot->id, 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move unit to a deleted parent.');

        $this->service->moveUnit($child, $deletedRoot);
    }

    public function test_rollback_on_failure_during_move()
    {
        $root1 = $this->service->createUnit(['name' => 'Root 1', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R1', 'is_active' => true]);
        $root2 = $this->service->createUnit(['name' => 'Root 2', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'R2', 'is_active' => true]);

        $child1 = $this->service->createUnit(['name' => 'Child 1', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'C1', 'parent_id' => $root1->id, 'is_active' => true]);
        $grandchild = $this->service->createUnit(['name' => 'Grandchild', 'unit_type' => OrganizationalUnitType::ZONAL_OFFICE->value, 'code' => 'GC', 'parent_id' => $child1->id, 'is_active' => true]);

        // We can simulate a failure by throwing an exception from a model event just to test rollback
        // Let's hook into the saving event of the grandchild to throw an exception
        OrganizationalUnit::saving(function ($model) use ($grandchild) {
            if ($model->id === $grandchild->id && $model->isDirty('path')) {
                throw new \Exception('Simulated failure');
            }
        });

        try {
            $this->service->moveUnit($child1, $root2);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('Simulated failure', $e->getMessage());
        }

        // Verify rollback
        $child1->refresh();
        $grandchild->refresh();

        $this->assertEquals($root1->id, $child1->parent_id);
        $this->assertEquals("/{$root1->id}/{$child1->id}/", $child1->path);
        $this->assertEquals("/{$root1->id}/{$child1->id}/{$grandchild->id}/", $grandchild->path);
    }
}
