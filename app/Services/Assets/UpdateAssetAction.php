<?php

namespace App\Services\Assets;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAssetAction
{
    public function __construct(
        private readonly AssetPlacementService $placementService
    ) {}

    /**
     * Update an existing asset record.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Asset $asset, array $data, ?User $actor = null): Asset
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            $lockedAsset = Asset::where('id', $asset->id)->lockForUpdate()->firstOrFail();
            $locationWasSubmitted = array_key_exists('location_id', $data);
            $destinationId = $data['location_id'] ?? null;
            $locationChanged = $locationWasSubmitted
                && (int) $destinationId !== (int) $lockedAsset->location_id;

            unset($data['location_id']);
            $lockedAsset->update($data);

            if ($locationChanged) {
                if (! $actor) {
                    throw new AuthorizationException('An authenticated actor is required to relocate an asset.');
                }

                if (! $destinationId) {
                    throw ValidationException::withMessages([
                        'location_id' => 'Use the asset relocation workflow to select an authorized destination.',
                    ]);
                }

                $destination = Location::query()->findOrFail($destinationId);
                $this->placementService->placeAsset(
                    $lockedAsset,
                    $destination,
                    $actor,
                    ['remarks' => 'Relocated while updating asset details.']
                );
            }

            return $lockedAsset->fresh();
        });
    }
}
