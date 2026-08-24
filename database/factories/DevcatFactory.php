<?php

namespace Database\Factories;

use App\Models\Devcat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Devcat>
 */
class DevcatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Devcat::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Junior Developer', 'Senior Developer', 'Lead Architect', 'DevOps Engineer', 'Full Stack Developer', 'Data Engineer']),
            'salary' => $this->faker->randomElement(['₹40,000 - ₹60,000', '₹60,000 - ₹90,000', '₹90,000 - ₹1,40,000', '₹1,50,000+']),
            'exp' => $this->faker->randomElement(['0-2 Years', '2-5 Years', '5-8 Years', '8+ Years']),
            'dev' => $this->faker->numberBetween(1, 10),
            'collab' => $this->faker->numberBetween(1, 20),
            'qualification' => $this->faker->randomElement(['B.Tech / B.E. in CS/IT', 'MCA / M.Sc. CS', 'B.Sc. in Computer Science', 'M.Tech in CS/Software Eng']),
        ];
    }
}
