<?php

namespace App\Services\Spatial;

use App\Exceptions\InvalidHierarchyMove;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class PhysicalHierarchyService
{
    /**
     * Create a new location and calculate its path.
     */
    public function createLocation(array $data): Location
    {
        return DB::transaction(function () use ($data) {
            $location = Location::create($data);
            $parentPath = '';

            if (!empty($location->parent_id)) {
                $parent = Location::find($location->parent_id);
                if ($parent) {
                    $parentPath = $parent->path;
                }
            }

            if (empty($parentPath)) {
                $location->path = '/' . $location->id . '/';
            } else {
                $location->path = rtrim($parentPath, '/') . '/' . $location->id . '/';
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
            if ($newParent) {
                if ($location->id === $newParent->id) {
                    throw new InvalidHierarchyMove('Cannot move location to itself.');
                }

                if (!$newParent->is_active) {
                    throw new InvalidHierarchyMove('Cannot move location to an inactive parent.');
                }

                if ($newParent->trashed()) {
                    throw new InvalidHierarchyMove('Cannot move location to a deleted parent.');
                }

                // Check for cycle (if new parent is a descendant of the location)
                if (str_starts_with((string)$newParent->path, (string)$location->path)) {
                    throw new InvalidHierarchyMove('Cannot move a location to its own descendant.');
                }
            }

            $oldPath = $location->path;

            if ($newParent) {
                $newPath = rtrim($newParent->path, '/') . '/' . $location->id . '/';
                $location->parent_id = $newParent->id;
            } else {
                $newPath = '/' . $location->id . '/';
                $location->parent_id = null;
            }

            $location->path = $newPath;
            $location->save();

            // Update all descendants
            $descendants = Location::withTrashed()
                ->where('path', 'LIKE', $oldPath . '%')
                ->where('id', '!=', $location->id)
                ->get();

            $oldPathLength = strlen($oldPath);
            foreach ($descendants as $descendant) {
                $descendant->path = $newPath . substr($descendant->path, $oldPathLength);
                $descendant->save();
            }
        });
    }
}
