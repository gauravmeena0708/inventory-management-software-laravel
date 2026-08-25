<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tasks.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, ?Task $task = null): bool
    {
        return $user->canViewInventory()
            && $task instanceof Task
            && app(OrganizationalVisibility::class)->canRead($user, $task->organizational_unit_id);
    }

    /**
     * Determine whether the user can create tasks.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, ?Task $task = null): bool
    {
        return $this->canWrite($user, $task)
            && $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::STOCK_OPERATOR);
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, ?Task $task = null): bool
    {
        return $this->canWrite($user, $task)
            && $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    private function canWrite(User $user, ?Task $task): bool
    {
        return $task instanceof Task
            && $task->organizational_unit_id !== null
            && $task->organizationalUnit()->active()->exists()
            && app(OrganizationalContext::class)->canWrite($user, $task->organizational_unit_id);
    }
}
