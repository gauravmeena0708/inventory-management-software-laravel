<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditObservation;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditObservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, AuditObservation $observation): bool
    {
        return app(AuditEngagementPolicy::class)->view($user, $observation->engagement);
    }

    public function issue(User $user, AuditObservation $observation): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::AUDITOR);
    }

    public function reply(User $user, AuditObservation $observation): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $observation->engagement->audited_organizational_unit_id);
    }

    public function settle(User $user, AuditObservation $observation): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::AUDITOR);
    }
}
