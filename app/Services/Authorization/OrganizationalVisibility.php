<?php

namespace App\Services\Authorization;

use App\Contracts\OrganizationalServiceIdentity;
use App\Enums\UserRole;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrganizationalVisibility
{
    /**
     * Restrict an owned model query to organizational units readable by the user.
     *
     * The user is always explicit: this service never depends on ambient Auth state.
     */
    public function apply(Builder $query, User $user, string $ownershipColumn = 'organizational_unit_id'): Builder
    {
        $unitIds = $this->readableUnitIds($user);

        if ($unitIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn(
            $query->qualifyColumn($ownershipColumn),
            $unitIds->all()
        );
    }

    /**
     * Determine whether a user can read records owned by the given unit.
     */
    public function canRead(User $user, int|string|null $unitId): bool
    {
        if (! $unitId) {
            return false;
        }

        return $this->readableUnitIds($user)->containsStrict((int) $unitId);
    }

    /**
     * Scope non-interactive work through an explicit, auditable identity.
     */
    public function applyForService(
        Builder $query,
        OrganizationalServiceIdentity $identity,
        string $ownershipColumn = 'organizational_unit_id'
    ): Builder {
        $unitIds = $this->serviceUnitIds($identity);

        if ($unitIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($query->qualifyColumn($ownershipColumn), $unitIds->all());
    }

    /**
     * Restrict locations through their active site's organizational mappings.
     */
    public function applyToLocations(Builder $query, User $user): Builder
    {
        $unitIds = $this->readableUnitIds($user);

        if ($unitIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where($query->qualifyColumn('is_active'), true)
            ->whereHas('site', function (Builder $siteQuery) use ($unitIds): void {
                $siteQuery
                    ->where('is_active', true)
                    ->whereHas('organizationalUnits', function (Builder $unitQuery) use ($unitIds): void {
                        $unitQuery->whereIn('organizational_units.id', $unitIds->all());
                    });
            });
    }

    /**
     * Resolve active, non-deleted readable unit IDs once for a query or policy check.
     *
     * @return Collection<int, int>
     */
    public function readableUnitIds(User $user): Collection
    {
        if ($user->hasRole(UserRole::ADMIN)) {
            return OrganizationalUnit::query()
                ->active()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();
        }

        $memberships = $user->activeOrganizationalUnits()
            ->get(['organizational_units.id', 'organizational_units.path']);

        if ($memberships->isEmpty()) {
            return collect();
        }

        $localIds = $memberships
            ->where('pivot.read_scope', 'local')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        $descendantPaths = $memberships
            ->where('pivot.read_scope', 'descendants')
            ->pluck('path')
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique()
            ->values();

        $descendantIds = collect();

        if ($descendantPaths->isNotEmpty()) {
            $descendantIds = OrganizationalUnit::query()
                ->active()
                ->where(function (Builder $query) use ($descendantPaths): void {
                    foreach ($descendantPaths as $path) {
                        $query->orWhere('path', 'like', $this->escapeLike($path).'%');
                    }
                })
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);
        }

        return $localIds
            ->merge($descendantIds)
            ->unique()
            ->values();
    }

    /** @return Collection<int, int> */
    public function serviceUnitIds(OrganizationalServiceIdentity $identity): Collection
    {
        $query = OrganizationalUnit::query()->active();

        if (! $identity->isOrganizationWide()) {
            $query->whereIn('id', $identity->organizationalUnitIds());
        }

        return $query->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
