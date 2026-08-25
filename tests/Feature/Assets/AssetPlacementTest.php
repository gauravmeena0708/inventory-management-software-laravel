<?php

namespace Tests\Feature\Assets;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetPlacement;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Assets\AssetPlacementBackfillService;
use App\Services\Assets\AssetPlacementService;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\UpdateAssetAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_placement_and_closes_previous(): void
    {
        [$unit, $user, $site] = $this->authorizedInventoryContext();
        $location1 = Location::factory()->create(['site_id' => $site->id]);
        $location2 = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $location1->id,
        ]);

        $service = new AssetPlacementService;
        $placement1 = $service->placeAsset($asset, $location1, $user, ['remarks' => 'First']);

        $this->assertEquals($location1->id, $asset->fresh()->location_id);
        $this->assertNull($placement1->removed_at);
        $this->assertNotNull($placement1->placed_at);

        // Move to location 2
        $placement2 = $service->placeAsset($asset, $location2, $user, ['remarks' => 'Second']);

        $this->assertEquals($location2->id, $asset->fresh()->location_id);
        $this->assertNotNull($placement1->fresh()->removed_at);
        $this->assertNull($placement1->fresh()->open_marker);
        $this->assertNull($placement2->removed_at);
        $this->assertTrue($placement2->open_marker);
        $this->assertEquals($placement2->id, $asset->fresh()->currentPlacement->id);
        $this->assertCount(2, $asset->fresh()->placements);
    }

    public function test_database_guard_prevents_two_open_placements_for_an_asset(): void
    {
        [$unit, $user, $site] = $this->authorizedInventoryContext();
        $location = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);
        (new AssetPlacementService)->placeAsset($asset, $location, $user);

        $this->expectException(QueryException::class);

        AssetPlacement::create([
            'asset_id' => $asset->id,
            'location_id' => $location->id,
            'placed_at' => now(),
            'open_marker' => true,
            'source' => 'test',
        ]);
    }

    public function test_service_rejects_user_without_source_write_access(): void
    {
        [$sourceUnit, , $sourceSite] = $this->authorizedInventoryContext();
        [$otherUnit, $otherManager] = $this->organizationalContext('OTHER');
        $sourceLocation = Location::factory()->create(['site_id' => $sourceSite->id]);
        $asset = Asset::factory()->create(['organizational_unit_id' => $sourceUnit->id]);

        $this->expectException(AuthorizationException::class);

        (new AssetPlacementService)->placeAsset($asset, $sourceLocation, $otherManager);
    }

    public function test_service_rejects_destination_outside_users_write_scope(): void
    {
        [$sourceUnit, $manager, $sourceSite] = $this->authorizedInventoryContext();
        [, , $otherSite] = $this->organizationalContext('OTHER');
        $sourceLocation = Location::factory()->create(['site_id' => $sourceSite->id]);
        $otherLocation = Location::factory()->create(['site_id' => $otherSite->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $sourceUnit->id,
            'location_id' => $sourceLocation->id,
        ]);

        $this->expectException(AuthorizationException::class);

        (new AssetPlacementService)->placeAsset($asset, $otherLocation, $manager);
    }

    public function test_service_rejects_source_location_outside_users_write_scope(): void
    {
        [$ownedUnit, $manager, $destinationSite] = $this->authorizedInventoryContext();
        [, , $otherSite] = $this->organizationalContext('OTHER');
        $sourceLocation = Location::factory()->create(['site_id' => $otherSite->id]);
        $destination = Location::factory()->create(['site_id' => $destinationSite->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $ownedUnit->id,
            'location_id' => $sourceLocation->id,
        ]);

        $this->expectException(AuthorizationException::class);

        (new AssetPlacementService)->placeAsset($asset, $destination, $manager);
    }

    public function test_service_fails_closed_for_unowned_asset_or_unmapped_destination(): void
    {
        [$unit, $manager] = $this->authorizedInventoryContext();
        $unmappedLocation = Location::factory()->create(['site_id' => null]);
        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        $this->expectException(AuthorizationException::class);

        (new AssetPlacementService)->placeAsset($asset, $unmappedLocation, $manager);
    }

    public function test_viewer_and_auditor_cannot_relocate_assets(): void
    {
        [$unit, , $site] = $this->authorizedInventoryContext();
        $location = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);

        foreach ([UserRole::VIEWER, UserRole::AUDITOR] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $user->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);

            try {
                (new AssetPlacementService)->placeAsset($asset, $location, $user);
                $this->fail("{$role->value} was unexpectedly allowed to relocate an asset.");
            } catch (AuthorizationException) {
                $this->assertNull($asset->fresh()->currentPlacement);
            }
        }
    }

    public function test_relocation_does_not_modify_assignment_history(): void
    {
        [$unit, $manager, $site] = $this->authorizedInventoryContext();
        $location = Location::factory()->create(['site_id' => $site->id]);
        $official = Official::factory()->create();
        $asset = Asset::factory()->create(['organizational_unit_id' => $unit->id]);
        $assignment = AssetAssignment::factory()->create([
            'asset_id' => $asset->id,
            'official_id' => $official->id,
            'returned_at' => null,
        ]);

        (new AssetPlacementService)->placeAsset($asset, $location, $manager);

        $this->assertDatabaseHas('asset_assignments', [
            'id' => $assignment->id,
            'returned_at' => null,
        ]);
        $this->assertCount(1, $asset->fresh()->assignments);
        $this->assertCount(1, $asset->fresh()->placements);
    }

    public function test_backfill_creates_one_current_placement_and_is_idempotent(): void
    {
        $location = Location::factory()->create();
        $asset = Asset::factory()->create(['location_id' => $location->id]);
        $service = new AssetPlacementBackfillService;

        $this->assertSame(1, $service->backfill());
        $this->assertSame(0, $service->backfill());
        $this->assertCount(1, $asset->fresh()->placements);
        $this->assertEquals($location->id, $asset->fresh()->currentPlacement->location_id);
        $this->assertSame('repair_backfill', $asset->fresh()->currentPlacement->source);
    }

    public function test_authorized_http_request_relocates_asset(): void
    {
        [$unit, $manager, $site] = $this->authorizedInventoryContext();
        $source = Location::factory()->create(['site_id' => $site->id]);
        $destination = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $source->id,
        ]);

        (new AssetPlacementService)->placeAsset($asset, $source, $manager);

        $this->actingAs($manager)
            ->postJson(route('assets.placements.store', $asset), [
                'location_id' => $destination->id,
                'remarks' => 'Moved through the HTTP endpoint.',
            ])
            ->assertOk()
            ->assertJsonPath('asset.location_id', $destination->id)
            ->assertJsonPath('placement.location_id', $destination->id);

        $asset->refresh();
        $this->assertEquals($destination->id, $asset->location_id);
        $this->assertEquals($destination->id, $asset->currentPlacement->location_id);
        $this->assertCount(2, $asset->placements);
    }

    public function test_asset_creation_records_ownership_and_initial_placement(): void
    {
        [$unit, $manager, $site] = $this->authorizedInventoryContext();
        $location = Location::factory()->create(['site_id' => $site->id]);

        $asset = app(CreateAssetAction::class)->execute([
            'name' => 'Newly received server',
            'asset_type' => 'server',
            'location_id' => $location->id,
        ], $manager);

        $this->assertEquals($unit->id, $asset->organizational_unit_id);
        $this->assertEquals($location->id, $asset->location_id);
        $this->assertEquals($location->id, $asset->currentPlacement->location_id);
        $this->assertSame('application', $asset->currentPlacement->source);
        $this->assertCount(1, $asset->placements);
    }

    public function test_asset_update_delegates_location_change_to_placement_service(): void
    {
        [$unit, $manager, $site] = $this->authorizedInventoryContext();
        $source = Location::factory()->create(['site_id' => $site->id]);
        $destination = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $source->id,
        ]);

        (new AssetPlacementService)->placeAsset($asset, $source, $manager);

        app(UpdateAssetAction::class)->execute($asset, [
            'name' => 'Updated asset name',
            'location_id' => $destination->id,
        ], $manager);

        $asset->refresh();
        $this->assertSame('Updated asset name', $asset->name);
        $this->assertEquals($destination->id, $asset->location_id);
        $this->assertEquals($destination->id, $asset->currentPlacement->location_id);
        $this->assertCount(2, $asset->placements);
        $this->assertCount(1, $asset->placements()->whereNotNull('removed_at')->get());
    }

    public function test_http_request_from_viewer_is_denied_without_side_effects(): void
    {
        [$unit, , $site] = $this->authorizedInventoryContext();
        $source = Location::factory()->create(['site_id' => $site->id]);
        $destination = Location::factory()->create(['site_id' => $site->id]);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $source->id,
        ]);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        $viewer->organizationalUnits()->attach($unit->id, ['write_scope' => 'local']);

        $this->actingAs($viewer)
            ->postJson(route('assets.placements.store', $asset), [
                'location_id' => $destination->id,
            ])
            ->assertForbidden();

        $this->assertEquals($source->id, $asset->fresh()->location_id);
        $this->assertDatabaseCount('asset_placements', 0);
    }

    /**
     * @return array{0: OrganizationalUnit, 1: User, 2: Site}
     */
    private function authorizedInventoryContext(): array
    {
        return $this->organizationalContext('LOCAL');
    }

    /**
     * @return array{0: OrganizationalUnit, 1: User, 2: Site}
     */
    private function organizationalContext(string $code): array
    {
        $unit = OrganizationalUnit::factory()->create(['code' => $code]);
        $user = User::factory()->inventoryManager()->create();
        $user->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'local',
        ]);
        $site = Site::create([
            'code' => "SITE-{$code}",
            'name' => "Site {$code}",
            'is_active' => true,
        ]);
        $site->organizationalUnits()->attach($unit->id);

        return [$unit, $user, $site];
    }
}
