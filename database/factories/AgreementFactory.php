<?php

namespace Database\Factories;

use App\Models\Agreement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Agreement_'. Str::random(5),
            'agency' => 'Agency_'. $this->faker->randomDigitNotNull,
            'file_id' => $this->faker->unique->numberBetween(1,250),
            'type' => $this->faker->randomElement(['Service','Product']),
            'expiry'=> $this->faker->dateTimeBetween('-2 years', '+1 years'),
            'annual_cost' => $this->faker->numberBetween(1000000,40000000),
            'frequency' => $this->faker->randomElement([1, 3, 4 ,6, 12]),
            'paid_till'=> $this->faker->dateTimeBetween('-1 years', '+1 years'),
            'remarks' => $this->faker->paragraph(1)
        ];
    }
}
