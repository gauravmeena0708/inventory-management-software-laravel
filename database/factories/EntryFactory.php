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
            'consumable_id' => \App\Models\Consumable::all()->random()->id,
            'type' => 0,
            'amount' => 10,
            'issuer_id' => \App\Models\Official::all()->random()->id,
            'stock' => 10
        ];
    }
}
