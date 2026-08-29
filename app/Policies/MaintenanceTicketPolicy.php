<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceTicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, MaintenanceTicket $ticket): bool
    {
        if (! $user->canViewInventory()) {
            return false;
        }

        return app(OrganizationalVisibility::class)->canRead($user, $ticket->organizational_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    public function update(User $user, MaintenanceTicket $ticket): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR)) {
            return false;
        }

        return app(OrganizationalContext::class)->canWrite($user, $ticket->organizational_unit_id);
    }

    public function resolve(User $user, MaintenanceTicket $ticket): bool
    {
        return $this->update($user, $ticket);
    }
}
