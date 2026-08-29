<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Consumable;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsumablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any consumables.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the consumable.
     */
    public function view(User $user, ?Consumable $consumable = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create consumables.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can update the consumable.
     */
    public function update(User $user, ?Consumable $consumable = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can delete the consumable.
     */
    public function delete(User $user, ?Consumable $consumable = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can post stock entries (purchase/issue).
     */
    public function postEntry(User $user, ?Consumable $consumable = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Determine whether the user can record a purchase entry.
     */
    public function purchase(User $user, ?Consumable $consumable = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Determine whether the user can record an issue entry.
     */
    public function issue(User $user, ?Consumable $consumable = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Determine whether the user can export consumable/stock data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR, UserRole::AUDITOR);
    }
}
