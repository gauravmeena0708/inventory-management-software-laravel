<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Official;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any officials.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the official.
     */
    public function view(User $user, ?Official $official = null): bool
    {
        if (! $user->canViewInventory() || ! $official) {
            return false;
        }

        return Official::query()
            ->visibleTo($user)
            ->whereKey($official->getKey())
            ->exists();
    }

    /**
     * Determine whether the user can create officials.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Determine whether the user can update the official.
     */
    public function update(User $user, ?Official $official = null): bool
    {
        return $this->canWrite($user, $official);
    }

    /**
     * Determine whether the user can delete the official.
     */
    public function delete(User $user, ?Official $official = null): bool
    {
        return $this->canWrite($user, $official);
    }

    /**
     * Determine whether the user can view sensitive personnel details.
     */
    public function viewSensitive(User $user, ?Official $official = null): bool
    {
        if (! $user->canViewSensitivePersonnel()) {
            return false;
        }

        return ! $official || $this->view($user, $official);
    }

    private function canWrite(User $user, ?Official $official): bool
    {
        if (! $official || ! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        $location = $official->location;
        if (! $location?->is_active || ! $location->site?->is_active) {
            return false;
        }

        $context = app(OrganizationalContext::class);

        return $location->site
            ->organizationalUnits()
            ->active()
            ->get()
            ->contains(fn ($unit): bool => $context->canWrite($user, $unit->id));
    }
}
