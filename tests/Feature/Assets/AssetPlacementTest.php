<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use App\Services\Assets\AssetPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_placement_and_closes_previous()
    {
        $user = User::factory()->create();
        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        $asset = Asset::factory()->create(['location_id' => $location1->id]);

        $service = new AssetPlacementService();
        $placement1 = $service->placeAsset($asset, $location1, $user, ['remarks' => 'First']);

        $this->assertEquals($location1->id, $asset->fresh()->location_id);
        $this->assertNull($placement1->removed_at);
        $this->assertNotNull($placement1->placed_at);

        // Move to location 2
        $placement2 = $service->placeAsset($asset, $location2, $user, ['remarks' => 'Second']);

        $this->assertEquals($location2->id, $asset->fresh()->location_id);
        $this->assertNotNull($placement1->fresh()->removed_at);
        $this->assertNull($placement2->removed_at);
    }
}
