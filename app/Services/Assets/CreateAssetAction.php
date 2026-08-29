<?php

namespace App\Services\Assets;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAssetAction
{
    public function __construct(
        private readonly AssetPlacementService $placementService,
        private readonly OrganizationalContext $organizationalContext
    ) {}

    /**
     * Create a new asset record.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?User $actor = null): Asset
    {
        return DB::transaction(function () use ($data, $actor) {
            if (! isset($data['status'])) {
                $data['status'] = AssetStatus::IN_STOCK;
            }

            $locationId = $data['location_id'] ?? null;
            $location = $locationId
                ? Location::query()->with('site.organizationalUnits')->findOrFail($locationId)
                : null;

            if ($location) {
                if (! $actor) {
                    throw ValidationException::withMessages([
                        'location_id' => 'An authenticated user is required to place an asset.',
                    ]);
                }

                $data['organizational_unit_id'] = $this->resolveOwningUnit(
                    $actor,
                    $location,
                    $data['organizational_unit_id'] ?? null
                )->id;
                unset($data['location_id']);
            }

            $asset = Asset::create($data);

            if ($location) {
                $this->placementService->placeAsset(
                    $asset,
                    $location,
                    $actor,
                    ['remarks' => 'Initial placement on asset creation.']
                );
            }

            return $asset->fresh();
        });
    }

    private function resolveOwningUnit(User $actor, Location $location, mixed $requestedUnitId): OrganizationalUnit
    {
        if (! $location->is_active || ! $location->site?->is_active) {
            throw ValidationException::withMessages([
                'location_id' => 'Assets can only be placed in an active mapped location.',
            ]);
        }

        $eligibleUnits = $location->site
            ->organizationalUnits
            ->filter(fn (OrganizationalUnit $unit): bool => $unit->is_active)
            ->filter(fn (OrganizationalUnit $unit): bool => $this->organizationalContext->canWrite($actor, $unit->id));

        if ($requestedUnitId) {
            $requestedUnit = $eligibleUnits->firstWhere('id', (int) $requestedUnitId);

            if (! $requestedUnit) {
                throw ValidationException::withMessages([
                    'organizational_unit_id' => 'The selected owning unit is not active, writable, and mapped to this site.',
                ]);
            }

            return $requestedUnit;
        }

        $activeUnit = $this->organizationalContext->getActiveContext($actor);
        if ($activeUnit && $eligibleUnits->contains('id', $activeUnit->id)) {
            return $activeUnit;
        }

        if ($eligibleUnits->count() === 1) {
            return $eligibleUnits->first();
        }

        throw ValidationException::withMessages([
            'organizational_unit_id' => $eligibleUnits->isEmpty()
                ? 'You do not have write access to an organizational unit mapped to this site.'
                : 'Select the organizational unit that will own this asset.',
        ]);
    }
}
