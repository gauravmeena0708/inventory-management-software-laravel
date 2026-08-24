<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'FILE-' . $this->faker->unique()->numberBetween(1000, 9999),
            'efile_number' => 'EFILE-' . $this->faker->numberBetween(10000, 99999),
            'physical_name' => 'Cabinet ' . $this->faker->randomLetter(),
            'physical_number' => 'Folder-' . $this->faker->numberBetween(1, 100),
            'subject' => $this->faker->sentence(4),
            'division' => $this->faker->randomElement(['IT', 'Finance', 'HR', 'Logistics', 'Operations']),
            'opened_at' => $this->faker->date(),
        ];
    }
}
