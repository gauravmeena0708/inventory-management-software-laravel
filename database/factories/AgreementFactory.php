<?php

namespace Database\Factories;

use App\Models\Agreement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agreement>
 */
class AgreementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Agreement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Agreement ' . $this->faker->words(3, true),
            'agency' => $this->faker->company(),
            'file_id' => null,
            'type' => $this->faker->randomElement(['Hardware Maintenance', 'Software License', 'Cloud Services', 'Facility Management']),
            'expiry' => now()->addYear()->toDateString(),
            'annual_cost' => 120000.00,
            'currency' => 'INR',
            'billing_interval_months' => 3,
            'billing_anchor_date' => now()->startOfYear()->toDateString(),
            'paid_till' => null,
            'remarks' => $this->faker->sentence(),
            'legacy_payload' => null,
        ];
    }

    /**
     * Indicate that the agreement has a monthly billing schedule.
     */
    public function monthly(float $annualCost = 120000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval_months' => 1,
            'annual_cost' => $annualCost,
        ]);
    }

    /**
     * Indicate that the agreement has a quarterly billing schedule.
     */
    public function quarterly(float $annualCost = 120000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval_months' => 3,
            'annual_cost' => $annualCost,
        ]);
    }

    /**
     * Indicate that the agreement has a semi-annual billing schedule.
     */
    public function semiAnnual(float $annualCost = 120000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval_months' => 6,
            'annual_cost' => $annualCost,
        ]);
    }

    /**
     * Indicate that the agreement has an annual billing schedule.
     */
    public function annual(float $annualCost = 120000.00): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval_months' => 12,
            'annual_cost' => $annualCost,
        ]);
    }

    /**
     * Indicate that the agreement is expiring soon.
     */
    public function expiringSoon(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry' => now()->addDays($days)->toDateString(),
        ]);
    }

    /**
     * Indicate that the agreement is already expired.
     */
    public function expired(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry' => now()->subDays($days)->toDateString(),
        ]);
    }
}
