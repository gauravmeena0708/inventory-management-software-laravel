<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, mixed $asset = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, mixed $asset = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, mixed $asset = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can assign the asset.
     */
    public function assign(User $user, mixed $asset = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can return the asset.
     */
    public function return(User $user, mixed $asset = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can decommission the asset.
     */
    public function decommission(User $user, mixed $asset = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can export asset data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::AUDITOR);
    }
}
