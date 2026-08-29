<?php

namespace Tests\Feature\Frontend;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PocUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_poc_mode_presents_only_core_navigation_to_regular_users(): void
    {
        config()->set('inventory.poc_ui_mode', true);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Inventory POC')
            ->assertSee('Available assets')
            ->assertSee('Assigned assets')
            ->assertSee('Consumables &amp; Stock', false)
            ->assertSee('People')
            ->assertSee('Offices &amp; Locations', false)
            ->assertSee('Inventory Report')
            ->assertDontSee('Agreements &amp; payments', false)
            ->assertDontSee('File registry');
    }

    public function test_poc_inventory_report_is_scoped_and_respects_export_permission(): void
    {
        config()->set('inventory.poc_ui_mode', true);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        $unit = OrganizationalUnit::factory()->create();
        $viewer->organizationalUnits()->attach($unit->id, ['read_scope' => 'local', 'write_scope' => 'none']);

        Asset::factory()->inStock()->create(['organizational_unit_id' => $unit->id]);
        Consumable::factory()->create(['in_stock' => 1, 'min_quantity' => 5]);

        $this->actingAs($viewer)
            ->get(route('reports.inventory'))
            ->assertOk()
            ->assertSee('Inventory report')
            ->assertSee('Low stock items')
            ->assertSee('Your role can view this report but cannot export data.')
            ->assertDontSee('Export Excel report');
    }

    public function test_admin_can_reach_advanced_modules_from_collapsed_section(): void
    {
        config()->set('inventory.poc_ui_mode', true);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Advanced modules')
            ->assertSee('Agreements &amp; payments', false)
            ->assertSee('Offices &amp; Locations', false)
            ->assertDontSee('Organization hierarchy');
    }
}
