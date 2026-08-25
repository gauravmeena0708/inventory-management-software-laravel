<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, mixed $asset = null): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    /**
     * Helper to verify organizational write access.
     */
    protected function checkWriteScope(User $user, mixed $asset): bool
    {
        if (! $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            return false;
        }

        // If the asset has no unit assigned yet, default to allowing if role permits
        // Alternatively, require it to be assigned.
        if (! $asset || ! $asset->organizational_unit_id) {
            return true;
        }

        $context = app(\App\Services\Organization\OrganizationalContext::class);
        return $context->canWrite($user, $asset->organizational_unit_id);
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, mixed $asset = null): bool
    {
        return $this->checkWriteScope($user, $asset);
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, mixed $asset = null): bool
    {
        return $this->checkWriteScope($user, $asset);
    }

    /**
     * Determine whether the user can assign the asset.
     */
    public function assign(User $user, mixed $asset = null): bool
    {
        return $this->checkWriteScope($user, $asset);
    }

    /**
     * Determine whether the user can return the asset.
     */
    public function return(User $user, mixed $asset = null): bool
    {
        return $this->checkWriteScope($user, $asset);
    }

    /**
     * Determine whether the user can decommission the asset.
     */
    public function decommission(User $user, mixed $asset = null): bool
    {
        return $this->checkWriteScope($user, $asset);
    }

    /**
     * Determine whether the user can relocate the asset (Asset Placement).
     */
    public function relocate(User $user, mixed $asset, \App\Models\Location $destination): bool
    {
        if (! $this->checkWriteScope($user, $asset)) {
            return false;
        }

        // Must also have write scope on destination location's organizational unit.
        if ($destination && $destination->site && $destination->site->organizationalUnits->isNotEmpty()) {
            $context = app(\App\Services\Organization\OrganizationalContext::class);
            $hasDestWrite = false;
            foreach ($destination->site->organizationalUnits as $unit) {
                if ($context->canWrite($user, $unit->id)) {
                    $hasDestWrite = true;
                    break;
                }
            }
            return $hasDestWrite;
        }

        // If the destination isn't mapped to an org unit, we allow it (for legacy).
        return true;
    }

    /**
     * Determine whether the user can export asset data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::AUDITOR);
    }
}
