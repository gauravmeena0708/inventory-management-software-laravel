<?php

namespace App\Services\Spatial;

use App\Enums\LocationType;
use App\Exceptions\InvalidHierarchyMove;
use App\Models\Location;
use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PhysicalHierarchyService
{
    /**
     * Structured parent types. OTHER remains permissive for legacy locations
     * until the migration reconciliation has classified them.
     *
     * @var array<string, list<LocationType>>
     */
    private const ALLOWED_PARENTS = [
        'BUILDING' => [],
        'FLOOR' => [LocationType::BUILDING],
        'ZONE' => [LocationType::BUILDING, LocationType::FLOOR],
        'ROOM' => [LocationType::FLOOR, LocationType::ZONE],
        'DATA_HALL' => [LocationType::FLOOR, LocationType::ZONE, LocationType::ROOM],
        'ROW' => [LocationType::ZONE, LocationType::ROOM, LocationType::DATA_HALL],
        'RACK' => [LocationType::ZONE, LocationType::ROOM, LocationType::DATA_HALL, LocationType::ROW],
        'WORKSTATION' => [LocationType::FLOOR, LocationType::ZONE, LocationType::ROOM],
        'SEAT' => [LocationType::ZONE, LocationType::ROOM, LocationType::WORKSTATION],
        'STORE' => [LocationType::BUILDING, LocationType::FLOOR, LocationType::ZONE, LocationType::ROOM],
        'BIN' => [LocationType::STORE],
        'NETWORK_POINT' => [
            LocationType::BUILDING,
            LocationType::FLOOR,
            LocationType::ZONE,
            LocationType::ROOM,
            LocationType::DATA_HALL,
            LocationType::ROW,
            LocationType::RACK,
            LocationType::WORKSTATION,
            LocationType::SEAT,
            LocationType::STORE,
        ],
        'OTHER' => [],
    ];

    /**
     * Create a site after validating geographic coordinates.
     */
    public function createSite(array $data): Site
    {
        $validated = Validator::make($data, [
            'code' => ['required', 'string', 'max:255', 'unique:sites,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'geofence_geojson' => ['nullable', 'array'],
            'timezone' => ['nullable', 'timezone:all'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        return Site::create($validated);
    }

    /**
     * Create a new location and calculate its path.
     */
    public function createLocation(array $data): Location
    {
        $data['location_type'] ??= LocationType::OTHER;
        $data['is_restricted'] ??= false;
        $data['is_active'] ??= true;

        Validator::make($data, [
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_type' => ['sometimes', Rule::enum(LocationType::class)],
            'local_x' => ['nullable', 'numeric', 'between:-99999999.99,99999999.99'],
            'local_y' => ['nullable', 'numeric', 'between:-99999999.99,99999999.99'],
            'local_z' => ['nullable', 'numeric', 'between:-99999999.99,99999999.99'],
        ])->validateWithBag('location');

        return DB::transaction(function () use ($data) {
            $parent = null;

            if (! empty($data['parent_id'])) {
                $parent = Location::query()->withTrashed()->lockForUpdate()->findOrFail($data['parent_id']);
                $this->assertUsableParent($parent);

                if (isset($data['site_id']) && $data['site_id'] !== $parent->site_id) {
                    throw new InvalidHierarchyMove('A child location must belong to the same site as its parent.');
                }

                $data['site_id'] = $parent->site_id;
            }

            $type = $this->locationType($data['location_type'] ?? LocationType::OTHER);
            $this->assertAllowedParentType($type, $parent);

            $location = Location::create($data);
            $parentPath = $parent?->path ?? '';

            if (empty($parentPath)) {
                $location->path = '/'.$location->id.'/';
            } else {
                $location->path = rtrim($parentPath, '/').'/'.$location->id.'/';
            }
            $location->save();

            return $location;
        });
    }

    /**
     * Move a location to a new parent, updating paths for it and its descendants.
     */
    public function moveLocation(Location $location, ?Location $newParent): void
    {
        DB::transaction(function () use ($location, $newParent) {
            $location = Location::query()->withTrashed()->lockForUpdate()->findOrFail($location->id);
            $newParent = $newParent === null
                ? null
                : Location::query()->withTrashed()->lockForUpdate()->findOrFail($newParent->id);

            if ($newParent) {
                if ($location->id === $newParent->id) {
                    throw new InvalidHierarchyMove('Cannot move location to itself.');
                }

                $this->assertUsableParent($newParent);
                $this->assertAllowedParentType($this->locationType($location->location_type), $newParent);

                // Check for cycle (if new parent is a descendant of the location)
                if (str_starts_with((string) $newParent->path, (string) $location->path)) {
                    throw new InvalidHierarchyMove('Cannot move a location to its own descendant.');
                }
            }

            $oldPath = $location->path;

            if ($newParent) {
                $newPath = rtrim($newParent->path, '/').'/'.$location->id.'/';
                $location->parent_id = $newParent->id;
                $location->site_id = $newParent->site_id;
            } else {
                $this->assertAllowedParentType($this->locationType($location->location_type), null);
                $newPath = '/'.$location->id.'/';
                $location->parent_id = null;
            }

            $location->path = $newPath;
            $location->save();

            // Update all descendants
            $descendants = Location::withTrashed()
                ->where('path', 'LIKE', $oldPath.'%')
                ->where('id', '!=', $location->id)
                ->get();

            $oldPathLength = strlen($oldPath);
            foreach ($descendants as $descendant) {
                $descendant->path = $newPath.substr($descendant->path, $oldPathLength);
                $descendant->site_id = $location->site_id;
                $descendant->save();
            }
        });
    }

    private function assertUsableParent(Location $parent): void
    {
        if (! $parent->is_active) {
            throw new InvalidHierarchyMove('Cannot use an inactive parent location.');
        }

        if ($parent->trashed()) {
            throw new InvalidHierarchyMove('Cannot use a deleted parent location.');
        }
    }

    private function assertAllowedParentType(LocationType $type, ?Location $parent): void
    {
        // Unclassified legacy locations intentionally remain permissive.
        if ($type === LocationType::OTHER || $parent?->location_type === LocationType::OTHER) {
            return;
        }

        $allowedParents = self::ALLOWED_PARENTS[$type->value];

        if ($parent === null && $type !== LocationType::BUILDING) {
            throw new InvalidHierarchyMove("{$type->value} locations require a structured parent.");
        }

        if ($parent !== null && ! in_array($this->locationType($parent->location_type), $allowedParents, true)) {
            throw new InvalidHierarchyMove(
                "{$type->value} cannot be placed beneath {$parent->location_type->value}."
            );
        }
    }

    private function locationType(LocationType|string|null $type): LocationType
    {
        return $type instanceof LocationType
            ? $type
            : LocationType::from($type ?? LocationType::OTHER->value);
    }
}
