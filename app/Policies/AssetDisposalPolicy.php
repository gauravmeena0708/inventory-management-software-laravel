<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetDisposal;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetDisposalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, AssetDisposal $disposal): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        return app(OrganizationalVisibility::class)->canRead($user, $disposal->organizational_unit_id);
    }

    public function recommend(User $user, AssetDisposal $disposal): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $disposal->organizational_unit_id);
    }

    public function approve(User $user, AssetDisposal $disposal): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $disposal->organizational_unit_id);
    }

    public function complete(User $user, AssetDisposal $disposal): bool
    {
        return $this->approve($user, $disposal);
    }
}
