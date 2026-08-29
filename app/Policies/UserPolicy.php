<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $user, ?User $model = null): bool
    {
        return $user->isAdmin() || ($model && $user->id === $model->id);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, ?User $model = null): bool
    {
        return $user->isAdmin() || ($model && $user->id === $model->id);
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, ?User $model = null): bool
    {
        return $user->isAdmin() && (!$model || $user->id !== $model->id);
    }

    /**
     * Determine whether the user can manage user roles and permissions.
     */
    public function manageRoles(User $user): bool
    {
        return $user->isAdmin();
    }
}
