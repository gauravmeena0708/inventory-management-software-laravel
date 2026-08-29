<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FileRecord;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class FileRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    public function view(User $user, FileRecord $file): bool
    {
        return $user->canViewInventory()
            && app(OrganizationalVisibility::class)->canRead($user, $file->organizational_unit_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    public function update(User $user, FileRecord $file): bool
    {
        return $this->canWrite($user, $file)
            && $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER, UserRole::FINANCE_OPERATOR);
    }

    public function delete(User $user, FileRecord $file): bool
    {
        return $this->canWrite($user, $file)
            && $user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER);
    }

    private function canWrite(User $user, FileRecord $file): bool
    {
        return $file->organizational_unit_id !== null
            && $file->organizationalUnit()->active()->exists()
            && app(OrganizationalContext::class)->canWrite($user, $file->organizational_unit_id);
    }
}
