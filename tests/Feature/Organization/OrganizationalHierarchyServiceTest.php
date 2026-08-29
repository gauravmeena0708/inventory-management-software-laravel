<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Exceptions\InvalidHierarchyMove;
use App\Models\OrganizationalUnit;
use App\Services\Organization\OrganizationalHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalHierarchyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrganizationalHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrganizationalHierarchyService;
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
        $this->assertSame(0, $unit->depth);
        $this->assertDatabaseHas('organizational_units', [
            'id' => $unit->id,
            'path' => "/{$unit->id}/",
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
        $this->assertSame(1, $child->depth);
    }

    public function test_moves_subtree_to_new_parent_and_updates_paths()
    {
        $root = $this->service->createUnit(['name' => 'EPFO', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $headOffice = $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id, 'is_active' => true]);
        $ndc = $this->service->createUnit(['name' => 'NDC', 'unit_type' => OrganizationalUnitType::NDC->value, 'code' => 'NDC', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $division = $this->service->createUnit(['name' => 'Division', 'unit_type' => OrganizationalUnitType::DIVISION->value, 'code' => 'DIV', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $grandchild = $this->service->createUnit(['name' => 'Branch', 'unit_type' => OrganizationalUnitType::BRANCH->value, 'code' => 'BR', 'parent_id' => $division->id, 'is_active' => true]);

        $this->assertSame(2, $division->depth);
        $this->assertSame(3, $grandchild->depth);

        $this->service->moveUnit($division, $ndc);

        $division->refresh();
        $grandchild->refresh();

        $this->assertEquals($ndc->id, $division->parent_id);
        $this->assertEquals("/{$root->id}/{$headOffice->id}/{$ndc->id}/{$division->id}/", $division->path);
        $this->assertEquals("/{$root->id}/{$headOffice->id}/{$ndc->id}/{$division->id}/{$grandchild->id}/", $grandchild->path);
        $this->assertSame(3, $division->depth);
        $this->assertSame(4, $grandchild->depth);
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
        $root = $this->service->createUnit(['name' => 'Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $headOffice = $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id, 'is_active' => true]);
        $inactiveNdc = $this->service->createUnit(['name' => 'NDC', 'unit_type' => OrganizationalUnitType::NDC->value, 'code' => 'NDC', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $inactiveNdc->update(['is_active' => false]);
        $child = $this->service->createUnit(['name' => 'Division', 'unit_type' => OrganizationalUnitType::DIVISION->value, 'code' => 'DIV', 'parent_id' => $headOffice->id, 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move unit to an inactive parent.');

        $this->service->moveUnit($child, $inactiveNdc);
    }

    public function test_rejects_moving_to_deleted_parent()
    {
        $root = $this->service->createUnit(['name' => 'Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $headOffice = $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id, 'is_active' => true]);
        $deletedNdc = $this->service->createUnit(['name' => 'NDC', 'unit_type' => OrganizationalUnitType::NDC->value, 'code' => 'NDC', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $deletedNdc->delete();
        $child = $this->service->createUnit(['name' => 'Division', 'unit_type' => OrganizationalUnitType::DIVISION->value, 'code' => 'DIV', 'parent_id' => $headOffice->id, 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot move unit to a deleted parent.');

        $this->service->moveUnit($child, $deletedNdc);
    }

    public function test_rollback_on_failure_during_move()
    {
        $root = $this->service->createUnit(['name' => 'Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $headOffice = $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id, 'is_active' => true]);
        $ndc = $this->service->createUnit(['name' => 'NDC', 'unit_type' => OrganizationalUnitType::NDC->value, 'code' => 'NDC', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $division = $this->service->createUnit(['name' => 'Division', 'unit_type' => OrganizationalUnitType::DIVISION->value, 'code' => 'DIV', 'parent_id' => $headOffice->id, 'is_active' => true]);
        $grandchild = $this->service->createUnit(['name' => 'Branch', 'unit_type' => OrganizationalUnitType::BRANCH->value, 'code' => 'BR', 'parent_id' => $division->id, 'is_active' => true]);

        // We can simulate a failure by throwing an exception from a model event just to test rollback
        // Let's hook into the saving event of the grandchild to throw an exception
        OrganizationalUnit::saving(function ($model) use ($grandchild) {
            if ($model->id === $grandchild->id && $model->isDirty('path')) {
                throw new \Exception('Simulated failure');
            }
        });

        try {
            $this->service->moveUnit($division, $ndc);
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertEquals('Simulated failure', $e->getMessage());
        }

        // Verify rollback
        $division->refresh();
        $grandchild->refresh();

        $this->assertEquals($headOffice->id, $division->parent_id);
        $this->assertEquals("/{$root->id}/{$headOffice->id}/{$division->id}/", $division->path);
        $this->assertEquals("/{$root->id}/{$headOffice->id}/{$division->id}/{$grandchild->id}/", $grandchild->path);
        $this->assertSame(2, $division->depth);
        $this->assertSame(3, $grandchild->depth);

        OrganizationalUnit::flushEventListeners();
        OrganizationalUnit::clearBootedModels();
    }

    public function test_rejects_a_second_root(): void
    {
        $this->service->createUnit(['name' => 'EPFO', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Only one EPFO root organizational unit is allowed.');

        $this->service->createUnit(['name' => 'Another Root', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT2', 'is_active' => true]);
    }

    public function test_rejects_creation_under_inactive_parent(): void
    {
        $root = $this->service->createUnit(['name' => 'EPFO', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $root->update(['is_active' => false]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot create a unit under an inactive parent.');

        $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id]);
    }

    public function test_rejects_creation_under_deleted_parent(): void
    {
        $root = $this->service->createUnit(['name' => 'EPFO', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);
        $root->delete();

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('Cannot create a unit under a missing or deleted parent.');

        $this->service->createUnit(['name' => 'Head Office', 'unit_type' => OrganizationalUnitType::HEAD_OFFICE->value, 'code' => 'HO', 'parent_id' => $root->id]);
    }

    public function test_rejects_invalid_parent_type(): void
    {
        $root = $this->service->createUnit(['name' => 'EPFO', 'unit_type' => OrganizationalUnitType::ROOT->value, 'code' => 'ROOT', 'is_active' => true]);

        $this->expectException(InvalidHierarchyMove::class);
        $this->expectExceptionMessage('DISTRICT_OFFICE cannot be placed under ROOT');

        $this->service->createUnit(['name' => 'District Office', 'unit_type' => OrganizationalUnitType::DISTRICT_OFFICE->value, 'code' => 'DO', 'parent_id' => $root->id]);
    }
}
