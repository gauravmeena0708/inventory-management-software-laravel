<?php

namespace App\Services\Organization;

use App\Enums\OrganizationalUnitType;
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
            $unitType = $this->resolveUnitType($data['unit_type'] ?? null);
            $parent = null;

            if (! empty($data['parent_id'])) {
                $parent = OrganizationalUnit::withTrashed()
                    ->lockForUpdate()
                    ->find($data['parent_id']);

                if (! $parent || $parent->trashed()) {
                    throw new InvalidHierarchyMove('Cannot create a unit under a missing or deleted parent.');
                }

                if (! $parent->is_active) {
                    throw new InvalidHierarchyMove('Cannot create a unit under an inactive parent.');
                }

                if (! $parent->path) {
                    throw new InvalidHierarchyMove('Cannot create a unit under a parent without a materialized path.');
                }
            }

            $this->assertValidParentType($unitType, $parent);

            if ($unitType === OrganizationalUnitType::ROOT) {
                $rootExists = OrganizationalUnit::withTrashed()
                    ->where('unit_type', OrganizationalUnitType::ROOT->value)
                    ->lockForUpdate()
                    ->exists();

                if ($rootExists) {
                    throw new InvalidHierarchyMove('Only one EPFO root organizational unit is allowed.');
                }
            }

            $data['unit_type'] = $unitType->value;
            $data['depth'] = $parent ? $parent->depth + 1 : 0;
            $data['path'] = null;

            $unit = OrganizationalUnit::create($data);
            $unit->path = $parent
                ? rtrim($parent->path, '/').'/'.$unit->id.'/'
                : '/'.$unit->id.'/';
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
            $lockedUnit = OrganizationalUnit::withTrashed()
                ->lockForUpdate()
                ->findOrFail($unit->getKey());

            if ($lockedUnit->trashed()) {
                throw new InvalidHierarchyMove('Cannot move a deleted organizational unit.');
            }

            $lockedParent = null;

            if ($newParent) {
                $lockedParent = OrganizationalUnit::withTrashed()
                    ->lockForUpdate()
                    ->findOrFail($newParent->getKey());

                if ($lockedUnit->id === $lockedParent->id) {
                    throw new InvalidHierarchyMove('Cannot move unit to itself.');
                }

                if (! $lockedParent->is_active) {
                    throw new InvalidHierarchyMove('Cannot move unit to an inactive parent.');
                }

                if ($lockedParent->trashed()) {
                    throw new InvalidHierarchyMove('Cannot move unit to a deleted parent.');
                }

                if (! $lockedParent->path || ! $lockedUnit->path) {
                    throw new InvalidHierarchyMove('Cannot move a unit with an invalid materialized path.');
                }

                if (str_starts_with($lockedParent->path, $lockedUnit->path)) {
                    throw new InvalidHierarchyMove('Cannot move a unit to its own descendant.');
                }
            }

            $unitType = $this->resolveUnitType($lockedUnit->unit_type);
            $this->assertValidParentType($unitType, $lockedParent);

            $oldPath = $lockedUnit->path;
            $oldDepth = $lockedUnit->depth;
            $newDepth = $lockedParent ? $lockedParent->depth + 1 : 0;

            if ($lockedParent) {
                $newPath = rtrim($lockedParent->path, '/').'/'.$lockedUnit->id.'/';
                $lockedUnit->parent_id = $lockedParent->id;
            } else {
                $newPath = '/'.$lockedUnit->id.'/';
                $lockedUnit->parent_id = null;
            }

            $lockedUnit->path = $newPath;
            $lockedUnit->depth = $newDepth;
            $lockedUnit->save();

            $descendants = OrganizationalUnit::withTrashed()
                ->where('path', 'LIKE', $oldPath.'%')
                ->where('id', '!=', $lockedUnit->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $oldPathLength = strlen($oldPath);
            $depthDelta = $newDepth - $oldDepth;

            foreach ($descendants as $descendant) {
                $descendant->path = $newPath.substr($descendant->path, $oldPathLength);
                $descendant->depth += $depthDelta;
                $descendant->save();
            }
        });
    }

    private function resolveUnitType(OrganizationalUnitType|string|null $unitType): OrganizationalUnitType
    {
        if ($unitType instanceof OrganizationalUnitType) {
            return $unitType;
        }

        if (! $unitType) {
            throw new InvalidHierarchyMove('An organizational unit type is required.');
        }

        try {
            return OrganizationalUnitType::from($unitType);
        } catch (\ValueError) {
            throw new InvalidHierarchyMove('Invalid organizational unit type.');
        }
    }

    private function assertValidParentType(
        OrganizationalUnitType $unitType,
        ?OrganizationalUnit $parent
    ): void {
        $parentType = $parent ? $this->resolveUnitType($parent->unit_type) : null;

        if (! $unitType->acceptsParent($parentType)) {
            $parentLabel = $parentType?->value ?? 'no parent';

            throw new InvalidHierarchyMove(
                "Organizational unit type {$unitType->value} cannot be placed under {$parentLabel}."
            );
        }
    }
}
