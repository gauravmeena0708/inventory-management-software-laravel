<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AssetTransfer;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, AssetTransfer $transfer): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        $vis = app(OrganizationalVisibility::class);

        return $vis->canRead($user, $transfer->from_organizational_unit_id)
            || $vis->canRead($user, $transfer->to_organizational_unit_id);
    }

    public function initiate(User $user, AssetTransfer $transfer): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $transfer->from_organizational_unit_id);
    }

    public function dispatch(User $user, AssetTransfer $transfer): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $transfer->from_organizational_unit_id);
    }

    public function receive(User $user, AssetTransfer $transfer): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $transfer->to_organizational_unit_id);
    }

    public function reject(User $user, AssetTransfer $transfer): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        $ctx = app(OrganizationalContext::class);

        return $ctx->canWrite($user, $transfer->from_organizational_unit_id)
            || $ctx->canWrite($user, $transfer->to_organizational_unit_id);
    }
}
