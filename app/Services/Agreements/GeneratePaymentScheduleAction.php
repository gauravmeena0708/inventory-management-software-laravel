<?php

namespace App\Services\Agreements;

use App\Enums\PaymentStatus;
use App\Models\Agreement;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GeneratePaymentScheduleAction
{
    /**
     * Generate an idempotent milestone payment schedule for an agreement.
     *
     * @return Collection<int, Payment>
     *
     * @throws InvalidArgumentException
     */
    public function execute(Agreement $agreement): Collection
    {
        if (empty($agreement->billing_anchor_date)) {
            throw new InvalidArgumentException("Agreement #{$agreement->id} must have a billing_anchor_date.");
        }

        if (empty($agreement->expiry)) {
            throw new InvalidArgumentException("Agreement #{$agreement->id} must have an expiry date.");
        }

        $interval = (int) $agreement->billing_interval_months;
        if ($interval < 1 || $interval > 12) {
            throw new InvalidArgumentException("Agreement #{$agreement->id} billing_interval_months must be between 1 and 12. [Given: {$interval}]");
        }

        $anchor = Carbon::parse($agreement->billing_anchor_date)->startOfDay();
        $expiry = Carbon::parse($agreement->expiry)->startOfDay();

        if ($anchor->gt($expiry)) {
            throw new InvalidArgumentException("Agreement #{$agreement->id} billing_anchor_date cannot be after expiry date.");
        }

        $amount = $agreement->annual_cost !== null
            ? round(((float) $agreement->annual_cost) * ($interval / 12), 2)
            : null;

        $isLastDayOfMonth = $anchor->copy()->endOfMonth()->day === $anchor->day;
        $anchorDay = $anchor->day;

        return DB::transaction(function () use ($agreement, $anchor, $expiry, $interval, $amount, $isLastDayOfMonth, $anchorDay) {
            /** @var Collection<int, Payment> $payments */
            $payments = collect();
            $step = 0;

            while (true) {
                $targetMonth = $anchor->copy()->startOfMonth()->addMonths($step * $interval);

                if ($isLastDayOfMonth) {
                    // Compare date-only values. Carbon's endOfMonth() otherwise leaves
                    // the time at 23:59:59, which incorrectly excludes an expiry on
                    // that same calendar day (for example April 30).
                    $dueDate = $targetMonth->copy()->endOfMonth()->startOfDay();
                } else {
                    $daysInMonth = $targetMonth->copy()->endOfMonth()->day;
                    $dueDate = $targetMonth->copy()->day(min($anchorDay, $daysInMonth));
                }

                if ($dueDate->gt($expiry)) {
                    break;
                }

                $scheduleKey = sha1("{$agreement->id}-{$dueDate->format('Y-m-d')}");

                /** @var Payment $payment */
                $payment = Payment::firstOrCreate(
                    ['schedule_key' => $scheduleKey],
                    [
                        'agreement_id' => $agreement->id,
                        'amount' => $amount,
                        'currency' => $agreement->currency ?? 'INR',
                        'due_date' => $dueDate->toDateString(),
                        'status' => PaymentStatus::PENDING,
                    ]
                );

                $payments->push($payment);
                $step++;
            }

            return $payments;
        });
    }
}
