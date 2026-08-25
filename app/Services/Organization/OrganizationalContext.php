<?php

namespace App\Services\Organization;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class OrganizationalContext
{
    public const SESSION_KEY = 'active_organizational_unit_id';

    /**
     * Validate and set the default organizational unit for a user.
     *
     * @param User $user
     * @param int $unitId
     * @return bool
     * @throws \Exception if unauthorized
     */
    public function setDefaultUnit(User $user, int $unitId): bool
    {
        $isActiveMember = $user->activeOrganizationalUnits()
            ->where('organizational_units.id', $unitId)
            ->exists();

        if (!$isActiveMember) {
            throw new \Exception("User is not an active member of this organizational unit.");
        }

        $user->default_organizational_unit_id = $unitId;
        return $user->save();
    }

    /**
     * Set the active UI context in session.
     *
     * @param User $user
     * @param int $unitId
     * @return void
     * @throws \Exception if unauthorized
     */
    public function setActiveContext(User $user, int $unitId): void
    {
        $isActiveMember = $user->activeOrganizationalUnits()
            ->where('organizational_units.id', $unitId)
            ->exists();

        if (!$isActiveMember) {
            throw new \Exception("User is not an active member of this organizational unit.");
        }

        Session::put(self::SESSION_KEY, $unitId);
    }

    /**
     * Get the active UI context. Returns default if no session.
     *
     * @param User $user
     * @return OrganizationalUnit|null
     */
    public function getActiveContext(User $user): ?OrganizationalUnit
    {
        $unitId = Session::get(self::SESSION_KEY);

        if ($unitId) {
            $unit = $user->activeOrganizationalUnits()->find($unitId);
            if ($unit) {
                return $unit;
            }
        }

        // Fallback to default
        if ($user->default_organizational_unit_id) {
            $defaultUnit = $user->activeOrganizationalUnits()->find($user->default_organizational_unit_id);
            if ($defaultUnit) {
                // Optionally set it in session too? Let's just return it for now.
                return $defaultUnit;
            }
        }

        return null;
    }
}
