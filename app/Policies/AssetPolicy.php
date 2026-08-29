<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
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
        if (! $user->canViewInventory() || ! $asset instanceof Asset) {
            return false;
        }

        return app(OrganizationalVisibility::class)
            ->canRead($user, $asset->organizational_unit_id);
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

        if (! $asset instanceof Asset || ! $asset->organizational_unit_id) {
            return false;
        }

        if (! $asset->organizationalUnit()->active()->exists()) {
            return false;
        }

        return app(OrganizationalContext::class)
            ->canWrite($user, $asset->organizational_unit_id);
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
    public function relocate(User $user, Asset $asset, Location $destination): bool
    {
        if (! $this->checkWriteScope($user, $asset)) {
            return false;
        }

        $source = $asset->location;
        if ($asset->location_id && (! $source || ! $this->checkLocationWriteScope($user, $source))) {
            return false;
        }

        return $this->checkLocationWriteScope($user, $destination);
    }

    private function checkLocationWriteScope(User $user, Location $location): bool
    {
        if (! $location->is_active || ! $location->site?->is_active) {
            return false;
        }

        $locationUnits = $location->site
            ->organizationalUnits()
            ->active()
            ->get();

        if ($locationUnits->isEmpty()) {
            return false;
        }

        $context = app(OrganizationalContext::class);

        return $locationUnits->contains(
            fn ($unit): bool => $context->canWrite($user, $unit->id)
        );
    }

    /**
     * Determine whether the user can export asset data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::AUDITOR);
    }
}
