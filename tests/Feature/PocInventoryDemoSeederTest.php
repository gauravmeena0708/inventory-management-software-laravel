<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Location;
use App\Models\Official;
use App\Models\User;
use Database\Seeders\PocInventoryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PocInventoryDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_visible_repeat_safe_poc_inventory_data(): void
    {
        $this->seed(PocInventoryDemoSeeder::class);
        $this->seed(PocInventoryDemoSeeder::class);

        $admin = User::query()->where('email', 'admin@inventory.local')->firstOrFail();

        $this->assertSame(8, Asset::query()->where('asset_tag', 'like', 'POC-%')->count());
        $this->assertSame(8, Asset::query()->visibleTo($admin)->where('asset_tag', 'like', 'POC-%')->count());
        $this->assertSame(5, Consumable::query()->where('sku', 'like', 'POC-%')->count());
        $this->assertSame(5, Official::query()->where('email', 'like', '%@poc.example.test')->count());
        $this->assertSame(4, Location::query()->where('code', 'like', 'POC-%')->count());
        $this->assertSame(4, Location::query()->visibleTo($admin)->where('code', 'like', 'POC-%')->count());
        $this->assertSame(3, AssetAssignment::query()->whereHas('asset', fn ($query) => $query->where('asset_tag', 'like', 'POC-%'))->count());
        $this->assertSame(10, Entry::query()->where('idempotency_key', 'like', 'POC-%')->count());
        $this->assertSame(2, Consumable::query()->where('sku', 'like', 'POC-%')->lowStock()->count());

        config()->set('inventory.poc_ui_mode', true);
        $this->actingAs($admin)
            ->get(route('organization.hierarchy'))
            ->assertOk()
            ->assertSee('HEAD OFFICE')
            ->assertSee('VIGILANCE WING')
            ->assertSee('INTERNAL AUDIT WING')
            ->assertSee('NATIONAL DATA CENTRE')
            ->assertSee('PDUNASS')
            ->assertSee('Zonal Training Institute, West Zone')
            ->assertSee('SSO - GANGTOK')
            ->assertSee('Manage rooms &amp; stores', false);
    }
}
