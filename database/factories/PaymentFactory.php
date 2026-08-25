<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Agreement;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = now()->addMonths(3)->toDateString();

        return [
            'agreement_id' => Agreement::factory(),
            'amount' => 30000.00,
            'currency' => 'INR',
            'due_date' => $dueDate,
            'paid_date' => null,
            'status' => PaymentStatus::PENDING,
            'invoice_number' => null,
            'remarks' => null,
            // Relationship factories are not expanded when dependent attribute
            // closures run, so do not interpolate AgreementFactory here.
            'schedule_key' => sha1($dueDate.'-'.Str::uuid()),
            'completed_by' => null,
            'legacy_id' => null,
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'paid_date' => null,
            'invoice_number' => null,
            'completed_by' => null,
        ]);
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(?User $user = null, ?string $invoice = 'INV-2026-001'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'paid_date' => now()->toDateString(),
            'invoice_number' => $invoice ?? 'INV-2026-'.$this->faker->numerify('#####'),
            'completed_by' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the payment is overdue.
     */
    public function overdue(int $days = 15): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $dueDate = now()->subDays($days)->toDateString();

            return [
                'status' => PaymentStatus::PENDING,
                'due_date' => $dueDate,
                'schedule_key' => sha1($dueDate.'-'.Str::uuid()),
            ];
        });
    }

    /**
     * Indicate that the payment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::CANCELLED,
        ]);
    }
}
