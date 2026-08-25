<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\AssetPlacement;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssetPlacementService
{
    /**
     * Places an asset at a specific location, closing the previous placement if any.
     */
    public function placeAsset(Asset $asset, Location $location, User $placedBy, array $details = []): AssetPlacement
    {
        return DB::transaction(function () use ($asset, $location, $placedBy, $details) {
            // Close the previous placement(s)
            $asset->placements()->whereNull('removed_at')->update([
                'removed_at' => now(),
            ]);

            // Update the asset denormalized location
            $asset->update([
                'location_id' => $location->id,
            ]);

            // Create new placement
            return $asset->placements()->create([
                'location_id' => $location->id,
                'placed_by' => $placedBy->id,
                'placed_at' => now(),
                'position_x' => $details['position_x'] ?? null,
                'position_y' => $details['position_y'] ?? null,
                'position_z' => $details['position_z'] ?? null,
                'rack_start_unit' => $details['rack_start_unit'] ?? null,
                'rack_unit_height' => $details['rack_unit_height'] ?? null,
                'remarks' => $details['remarks'] ?? null,
            ]);
        });
    }
}
