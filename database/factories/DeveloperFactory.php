<?php

namespace Database\Factories;

use App\Models\Devcat;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Developer>
 */
class DeveloperFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Developer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'reporting_id' => null,
            'category_id' => null,
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'salary' => (string) $this->faker->numberBetween(40000, 200000),
            'status' => 'active',
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Mark the developer as active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Mark the developer as discontinued.
     */
    public function discontinued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'discontinued',
        ]);
    }

    /**
     * Associate the developer with a category.
     */
    public function withCategory(?Devcat $category = null): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category?->id ?? Devcat::factory(),
        ]);
    }

    /**
     * Associate the developer with a reporting manager/user.
     */
    public function withReportingUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reporting_id' => $user?->id ?? User::factory(),
        ]);
    }
}
