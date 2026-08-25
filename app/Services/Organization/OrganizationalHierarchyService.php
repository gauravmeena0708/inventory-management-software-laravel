<?php

namespace App\Services\Organization;

use App\Exceptions\InvalidHierarchyMove;
use App\Models\OrganizationalUnit;
use Illuminate\Support\Facades\DB;

class OrganizationalHierarchyService
{
    /**
     * Create a new organizational unit and calculate its path.
     */
    public function createUnit(array $data): OrganizationalUnit
    {
        return DB::transaction(function () use ($data) {
            $unit = OrganizationalUnit::create($data);
            $parentPath = '';

            if (!empty($unit->parent_id)) {
                $parent = OrganizationalUnit::find($unit->parent_id);
                if ($parent) {
                    $parentPath = $parent->path;
                }
            }

            if (empty($parentPath)) {
                $unit->path = '/' . $unit->id . '/';
            } else {
                $unit->path = rtrim($parentPath, '/') . '/' . $unit->id . '/';
            }
            $unit->save();

            return $unit;
        });
    }

    /**
     * Move an organizational unit to a new parent, updating paths for it and its descendants.
     */
    public function moveUnit(OrganizationalUnit $unit, ?OrganizationalUnit $newParent): void
    {
        DB::transaction(function () use ($unit, $newParent) {
            if ($newParent) {
                if ($unit->id === $newParent->id) {
                    throw new InvalidHierarchyMove('Cannot move unit to itself.');
                }

                if (!$newParent->is_active) {
                    throw new InvalidHierarchyMove('Cannot move unit to an inactive parent.');
                }

                if ($newParent->trashed()) {
                    throw new InvalidHierarchyMove('Cannot move unit to a deleted parent.');
                }

                // Check for cycle (if new parent is a descendant of the unit)
                if (str_starts_with($newParent->path, $unit->path)) {
                    throw new InvalidHierarchyMove('Cannot move a unit to its own descendant.');
                }
            }

            $oldPath = $unit->path;

            if ($newParent) {
                $newPath = rtrim($newParent->path, '/') . '/' . $unit->id . '/';
                $unit->parent_id = $newParent->id;
            } else {
                $newPath = '/' . $unit->id . '/';
                $unit->parent_id = null;
            }

            $unit->path = $newPath;
            $unit->save();

            // Update all descendants
            // We use DB::table or eloquent and iterate to be DB-agnostic. 
            // Depending on scale this could be a large query, but works correctly with chunks if needed.
            // A direct SQL query could use string operations if we ensure MySQL/Postgres specifics.
            $descendants = OrganizationalUnit::withTrashed()
                ->where('path', 'LIKE', $oldPath . '%')
                ->where('id', '!=', $unit->id)
                ->get();

            $oldPathLength = strlen($oldPath);
            foreach ($descendants as $descendant) {
                $descendant->path = $newPath . substr($descendant->path, $oldPathLength);
                $descendant->save();
            }
        });
    }
}
