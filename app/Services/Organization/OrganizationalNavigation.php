<?php

namespace App\Services\Organization;

use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrganizationalNavigation
{
    public function __construct(
        private readonly OrganizationalContext $context,
        private readonly OrganizationalVisibility $visibility
    ) {}

    /** @return Collection<int, OrganizationalUnit> */
    public function contexts(User $user): Collection
    {
        return $user->activeOrganizationalUnits()
            ->orderBy('organizational_units.name')
            ->get();
    }

    /**
     * Resolve the readable units represented by the selected UI context.
     * The context narrows navigation; it never grants permissions.
     *
     * @return Collection<int, int>
     */
    public function contextUnitIds(User $user): Collection
    {
        $readableIds = $this->visibility->readableUnitIds($user);
        $active = $this->context->getActiveContext($user);

        if (! $active) {
            return $readableIds;
        }

        $membership = $user->activeOrganizationalUnits()
            ->whereKey($active->getKey())
            ->first();

        if (! $membership) {
            return collect();
        }

        $ids = collect([(int) $active->getKey()]);
        if ($membership->pivot->read_scope === 'descendants' && filled($active->path)) {
            $ids = OrganizationalUnit::query()
                ->active()
                ->where('path', 'like', $this->escapeLike($active->path).'%')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);
        }

        return $ids->intersect($readableIds)->values();
    }

    /** @return Collection<int, OrganizationalUnit> */
    public function writableUnits(User $user): Collection
    {
        return OrganizationalUnit::query()
            ->active()
            ->whereIn('id', $this->contextUnitIds($user)->all())
            ->orderBy('name')
            ->get()
            ->filter(fn (OrganizationalUnit $unit): bool => $this->context->canWrite($user, $unit->id))
            ->values();
    }

    /** @return Collection<int, Site> */
    public function sites(User $user, bool $writable = false): Collection
    {
        $unitIds = $writable
            ? $this->writableUnits($user)->pluck('id')
            : $this->contextUnitIds($user);

        if ($unitIds->isEmpty()) {
            return collect();
        }

        return Site::query()
            ->where('is_active', true)
            ->whereHas('organizationalUnits', function (Builder $query) use ($unitIds): void {
                $query->whereIn('organizational_units.id', $unitIds->all());
            })
            ->orderBy('name')
            ->get();
    }

    public function locationsQuery(User $user, bool $writable = false): Builder
    {
        $siteIds = $this->sites($user, $writable)->pluck('id');

        $query = Location::query()->visibleTo($user);
        if ($siteIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('site_id', $siteIds->all());
    }

    /** @return Collection<int, Location> */
    public function locations(User $user, bool $writable = false): Collection
    {
        return $this->locationsQuery($user, $writable)
            ->with(['site', 'parent'])
            ->orderBy('path')
            ->orderBy('name')
            ->get();
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
