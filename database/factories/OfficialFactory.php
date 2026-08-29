<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Official;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Official>
 */
class OfficialFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Official::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'title' => $this->faker->title(),
            'designation' => $this->faker->jobTitle(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Operations', 'Finance', 'HR', 'Logistics']),
            'location_id' => null,
        ];
    }

    /**
     * Associate the official with a location.
     */
    public function forLocation(?Location $location = null): static
    {
        return $this->state(fn (array $attributes) => [
            'location_id' => $location?->id ?? Location::factory(),
        ]);
    }
}
