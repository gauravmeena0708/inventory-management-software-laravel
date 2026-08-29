<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\SpatialMap;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpatialMapPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(
            UserRole::ADMIN,
            UserRole::INVENTORY_MANAGER,
            UserRole::AUDITOR
        );
    }

    public function view(User $user, SpatialMap $map): bool
    {
        return $this->viewAny($user)
            && $map->location !== null
            && $user->can('view', $map->location);
    }

    public function create(User $user, Location $location): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)
            && $user->can('update', $location);
    }

    public function update(User $user, SpatialMap $map): bool
    {
        return $map->location !== null && $this->create($user, $map->location);
    }

    public function delete(User $user, SpatialMap $map): bool
    {
        return $this->update($user, $map);
    }
}
