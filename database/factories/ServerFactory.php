<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Server::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            'asset_sr' => Str::random(12),
            'location' => Str::random(11),
            'asset_tag' => Str::random(11),
            'covered' => Str::random(11),
            'purchase_date' => $this->faker->dateTimeBetween('-1 years', '+2 years'),
            'purchase_cost' =>10000,
            'description' => Str::random(12),
            'end_of_sale' => $this->faker->dateTimeBetween('-3 years', '-1 years'),
            'end_of_support' => $this->faker->dateTimeBetween('-1 years', '+2 years'),
            'contract_expiry' => $this->faker->dateTimeBetween('-1 years', '+2 years'),
            'amc_cost'=> 12000,
            'contract_type' =>Str::random(13),
            'contracthash' =>Str::random(13),
            'address' =>Str::random(13),
            'city' =>Str::random(13),
            'state' =>Str::random(13),
            'country' =>Str::random(13),
            'terms' =>Str::random(13),
            'duplicated' =>Str::random(13),
            'timeout' =>Str::random(13),    
        ];
    }
}
