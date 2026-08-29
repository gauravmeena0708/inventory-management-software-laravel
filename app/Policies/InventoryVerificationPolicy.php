<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InventoryVerification;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryVerificationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, InventoryVerification $verification): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        return app(OrganizationalVisibility::class)->canRead($user, $verification->organizational_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::AUDITOR);
    }

    public function verify(User $user, InventoryVerification $verification): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR, UserRole::AUDITOR)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $verification->organizational_unit_id);
    }

    public function certify(User $user, InventoryVerification $verification): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $verification->organizational_unit_id);
    }
}
