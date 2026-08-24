<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Agreement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AgreementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any agreements.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the agreement.
     */
    public function view(User $user, ?Agreement $agreement = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create agreements.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can update the agreement.
     */
    public function update(User $user, ?Agreement $agreement = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can delete the agreement.
     */
    public function delete(User $user, ?Agreement $agreement = null): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can export agreement data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR, UserRole::AUDITOR);
    }
}
