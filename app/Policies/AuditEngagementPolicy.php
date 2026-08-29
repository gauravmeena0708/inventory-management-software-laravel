<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditEngagement;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditEngagementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, AuditEngagement $engagement): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        $canReadAudited = app(OrganizationalVisibility::class)->canRead($user, $engagement->audited_organizational_unit_id);
        $canReadAuditing = $engagement->auditing_organizational_unit_id
            ? app(OrganizationalVisibility::class)->canRead($user, $engagement->auditing_organizational_unit_id)
            : false;

        return $canReadAudited || $canReadAuditing;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::AUDITOR);
    }

    public function update(User $user, AuditEngagement $engagement): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::AUDITOR);
    }
}
