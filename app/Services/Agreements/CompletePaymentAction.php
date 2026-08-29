<?php

namespace App\Services\Agreements;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompletePaymentAction
{
    /**
     * Complete a payment, assign user and invoice details, and update the agreement paid_till date.
     *
     * @throws InvalidArgumentException
     */
    public function execute(
        Payment $payment,
        User $user,
        string $invoiceNumber,
        ?Carbon $paidDate = null
    ): Payment {
        if (blank($invoiceNumber)) {
            throw new InvalidArgumentException('An invoice number is required to complete a payment.');
        }

        return DB::transaction(function () use ($payment, $user, $invoiceNumber, $paidDate) {
            $paymentDate = ($paidDate ?? now())->copy()->startOfDay();

            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'invoice_number' => trim($invoiceNumber),
                'paid_date' => $paymentDate->toDateString(),
                'completed_by' => $user->id,
            ]);

            $agreement = $payment->agreement;
            if ($agreement) {
                $paymentDueDate = Carbon::parse($payment->due_date)->startOfDay();
                $currentPaidTill = $agreement->paid_till ? Carbon::parse($agreement->paid_till)->startOfDay() : null;

                if ($currentPaidTill === null || $paymentDueDate->gt($currentPaidTill)) {
                    $agreement->update([
                        'paid_till' => $paymentDueDate->toDateString(),
                    ]);
                }
            }

            return $payment->fresh();
        });
    }
}
