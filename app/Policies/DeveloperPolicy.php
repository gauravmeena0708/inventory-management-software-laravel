<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeveloperPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any developers.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the developer.
     */
    public function view(User $user, ?Developer $developer = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create developers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can update the developer.
     */
    public function update(User $user, ?Developer $developer = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can delete the developer.
     */
    public function delete(User $user, ?Developer $developer = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can view sensitive developer details (salary, contact).
     */
    public function viewSensitive(User $user, ?Developer $developer = null): bool
    {
        return $user->canViewSensitivePersonnel();
    }
}
