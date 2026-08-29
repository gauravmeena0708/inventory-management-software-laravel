<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, AssetCategory $category): bool
    {
        return $user->canViewInventory();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    public function update(User $user, AssetCategory $category): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    public function delete(User $user, AssetCategory $category): bool
    {
        return $user->hasRole(UserRole::ADMIN);
    }
}
