<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, ReportRun $run): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        return app(OrganizationalVisibility::class)->canRead($user, $run->organizational_unit_id);
    }

    public function generate(User $user): bool
    {
        return $user->hasRole(
            UserRole::ADMIN,
            UserRole::INVENTORY_MANAGER,
            UserRole::STOCK_OPERATOR,
            UserRole::FINANCE_OPERATOR,
            UserRole::AUDITOR
        );
    }

    public function certify(User $user, ReportRun $run): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $run->organizational_unit_id);
    }

    public function supersede(User $user, ReportRun $run): bool
    {
        return $this->certify($user, $run);
    }

    public function export(User $user, ReportRun $run): bool
    {
        return $this->view($user, $run);
    }
}
