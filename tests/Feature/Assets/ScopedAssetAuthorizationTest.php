<?php

namespace Tests\Feature\Assets;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedAssetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_update_asset_even_if_locally_assigned()
    {
        $unit = OrganizationalUnit::factory()->create();
        $user = User::factory()->create(['role' => UserRole::VIEWER]);
        $user->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);

        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        $this->assertFalse($user->can('update', $asset));
    }

    public function test_inventory_manager_can_update_asset_if_locally_assigned()
    {
        $unit = OrganizationalUnit::factory()->create();
        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $user->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);

        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        $this->assertTrue($user->can('update', $asset));
    }

    public function test_inventory_manager_cannot_update_asset_if_outside_scope()
    {
        $unit = OrganizationalUnit::factory()->create();
        $otherUnit = OrganizationalUnit::factory()->create();

        $user = User::factory()->create(['role' => UserRole::INVENTORY_MANAGER]);
        $user->organizationalUnits()->attach($otherUnit->id, ['write_scope' => 'local']); // assigned elsewhere

        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        $this->assertFalse($user->can('update', $asset));
    }

    public function test_inventory_manager_cannot_update_asset_without_organizational_ownership(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $user = User::factory()->inventoryManager()->create();
        $user->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);
        $asset = Asset::factory()->create(['organizational_unit_id' => null]);

        $this->assertFalse($user->can('update', $asset));
    }

    public function test_auditor_cannot_update_asset_even_if_locally_assigned(): void
    {
        $unit = OrganizationalUnit::factory()->create();
        $user = User::factory()->auditor()->create();
        $user->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);
        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        $this->assertFalse($user->can('update', $asset));
    }
}
