<?php

namespace App\Services\Assets;

use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetPlacement;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AssetPlacementService
{
    public function __construct(
        private readonly ?RecordLifecycleEventAction $recordLifecycleEvent = null
    ) {}

    /**
     * Places an asset at a specific location, closing the previous placement if any.
     */
    public function placeAsset(Asset $asset, Location $location, User $placedBy, array $details = []): AssetPlacement
    {
        return DB::transaction(function () use ($asset, $location, $placedBy, $details) {
            $lockedAsset = Asset::query()
                ->lockForUpdate()
                ->findOrFail($asset->getKey());
            $lockedLocation = Location::query()
                ->with('site.organizationalUnits')
                ->lockForUpdate()
                ->findOrFail($location->getKey());

            Gate::forUser($placedBy)->authorize(
                'relocate',
                [$lockedAsset, $lockedLocation]
            );

            $validated = Validator::make($details, [
                'position_x' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
                'position_y' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
                'position_z' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
                'rack_start_unit' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'rack_unit_height' => ['nullable', 'integer', 'min:1', 'max:1000'],
                'remarks' => ['nullable', 'string', 'max:5000'],
            ])->validate();

            $now = now();
            $fromLocationId = $lockedAsset->location_id;

            $openPlacements = AssetPlacement::query()
                ->where('asset_id', $lockedAsset->id)
                ->whereNull('removed_at')
                ->lockForUpdate()
                ->get();

            $isRelocation = $openPlacements->isNotEmpty() || $fromLocationId !== null;

            foreach ($openPlacements as $openPlacement) {
                $openPlacement->update([
                    'removed_at' => $now,
                    'open_marker' => null,
                ]);
            }

            $lockedAsset->location_id = $lockedLocation->id;
            $lockedAsset->save();

            $placement = $lockedAsset->placements()->create([
                'location_id' => $lockedLocation->id,
                'placed_by' => $placedBy->id,
                'placed_at' => $now,
                'open_marker' => true,
                'source' => 'application',
                'position_x' => $validated['position_x'] ?? null,
                'position_y' => $validated['position_y'] ?? null,
                'position_z' => $validated['position_z'] ?? null,
                'rack_start_unit' => $validated['rack_start_unit'] ?? null,
                'rack_unit_height' => $validated['rack_unit_height'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Emit PLACED or RELOCATED lifecycle event
            $lifecycleAction = $this->recordLifecycleEvent ?? app(RecordLifecycleEventAction::class);
            $lifecycleAction->execute(
                $lockedAsset,
                $isRelocation ? LifecycleEventType::RELOCATED : LifecycleEventType::PLACED,
                $placedBy,
                [
                    'occurred_at' => $now,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => $lockedLocation->id,
                    'reference_type' => 'AssetPlacement',
                    'reference_id' => $placement->id,
                    'remarks' => $validated['remarks'] ?? ($isRelocation ? 'Asset relocated.' : 'Asset placed at location.'),
                    'metadata' => [
                        'location_name' => $lockedLocation->name,
                        'position_x' => $validated['position_x'] ?? null,
                        'position_y' => $validated['position_y'] ?? null,
                        'rack_start_unit' => $validated['rack_start_unit'] ?? null,
                    ],
                ]
            );

            return $placement;
        }, 3);
    }
}
