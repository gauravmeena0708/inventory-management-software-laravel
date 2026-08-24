<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Official;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any officials.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the official.
     */
    public function view(User $user, ?Official $official = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create officials.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can update the official.
     */
    public function update(User $user, ?Official $official = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can delete the official.
     */
    public function delete(User $user, ?Official $official = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can view sensitive personnel details.
     */
    public function viewSensitive(User $user, ?Official $official = null): bool
    {
        return $user->canViewSensitivePersonnel();
    }
}
