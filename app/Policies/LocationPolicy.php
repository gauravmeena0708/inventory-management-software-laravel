<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, Location $location): bool
    {
        return $user->canViewInventory()
            && Location::query()->visibleTo($user)->whereKey($location->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->canWrite($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->canWrite($user, $location);
    }

    private function canWrite(User $user, Location $location): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        if (! $location->is_active || ! $location->site?->is_active) {
            return false;
        }

        $context = app(OrganizationalContext::class);

        return $location->site
            ->organizationalUnits()
            ->active()
            ->get()
            ->contains(fn ($unit): bool => $context->canWrite($user, $unit->id));
    }
}
