<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalUnitSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_organizational_unit_with_all_columns()
    {
        $unit = OrganizationalUnit::factory()->create([
            'code' => 'ROOT_01',
            'name' => 'Root Unit',
            'unit_type' => OrganizationalUnitType::ROOT,
            'path' => 'ROOT_01',
            'depth' => 0,
            'is_active' => true,
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertDatabaseHas('organizational_units', [
            'id' => $unit->id,
            'code' => 'ROOT_01',
            'name' => 'Root Unit',
            'unit_type' => 'ROOT',
            'path' => 'ROOT_01',
            'depth' => 0,
            'is_active' => 1,
        ]);

        // Test Casts
        $retrieved = OrganizationalUnit::find($unit->id);
        $this->assertInstanceOf(OrganizationalUnitType::class, $retrieved->unit_type);
        $this->assertEquals(OrganizationalUnitType::ROOT, $retrieved->unit_type);
        $this->assertTrue($retrieved->is_active);
        $this->assertIsArray($retrieved->metadata);
        $this->assertEquals('value', $retrieved->metadata['key']);
    }

    public function test_code_must_be_unique()
    {
        OrganizationalUnit::factory()->create(['code' => 'DUPLICATE']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/Integrity constraint violation: 19 UNIQUE constraint failed|Duplicate entry/');

        OrganizationalUnit::factory()->create(['code' => 'DUPLICATE']);
    }

    public function test_can_establish_parent_child_relationship()
    {
        $parent = OrganizationalUnit::factory()->create();
        $child = OrganizationalUnit::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_soft_deletes_are_applied()
    {
        $unit = OrganizationalUnit::factory()->create();
        $unitId = $unit->id;
        
        $unit->delete();

        $this->assertSoftDeleted('organizational_units', ['id' => $unitId]);
    }
}
