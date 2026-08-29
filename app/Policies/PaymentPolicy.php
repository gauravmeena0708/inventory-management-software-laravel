<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewInventory();
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, ?Payment $payment = null): bool
    {
        return $user->canViewInventory()
            && $payment instanceof Payment
            && Payment::query()->visibleTo($user)->whereKey($payment->id)->exists();
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, ?Payment $payment = null): bool
    {
        return $this->canWrite($user, $payment)
            && $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, ?Payment $payment = null): bool
    {
        return $this->canWrite($user, $payment)
            && $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can mark payment as completed.
     */
    public function complete(User $user, ?Payment $payment = null): bool
    {
        return $this->canWrite($user, $payment)
            && $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can cancel the payment.
     */
    public function cancel(User $user, ?Payment $payment = null): bool
    {
        return $this->canWrite($user, $payment)
            && $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR);
    }

    /**
     * Determine whether the user can export payment data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(UserRole::ADMIN, UserRole::FINANCE_OPERATOR, UserRole::AUDITOR);
    }

    private function canWrite(User $user, ?Payment $payment): bool
    {
        $unitId = $payment?->agreement?->organizational_unit_id;

        return $unitId !== null
            && $payment->agreement->organizationalUnit()->active()->exists()
            && app(OrganizationalContext::class)->canWrite($user, $unitId);
    }
}
