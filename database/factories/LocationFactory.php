<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city() . ' Office',
            'sublocation' => 'Room ' . $this->faker->numberBetween(100, 999),
            'building' => 'Building ' . $this->faker->randomLetter(),
            'floor' => (string) $this->faker->numberBetween(1, 10),
            'description' => $this->faker->sentence(),
        ];
    }
}
