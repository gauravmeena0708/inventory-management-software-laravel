<?php

namespace Database\Factories;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Entry::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        
        return [
            'consumable_id' => $this->faker->unique()->numberBetween(1,82),
            'type' => 0,
            'amount' => 10,
            'issuer_id' => 5,
            'stock' => 10
        ];
    }
}
