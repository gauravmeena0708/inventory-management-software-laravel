<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Acquisition;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcquisitionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, Acquisition $acquisition): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        return app(OrganizationalVisibility::class)->canRead($user, $acquisition->organizational_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    public function update(User $user, Acquisition $acquisition): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $acquisition->organizational_unit_id);
    }
}
