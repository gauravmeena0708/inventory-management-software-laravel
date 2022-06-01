<?php

namespace Database\Factories;

use App\Models\Devcat;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     * @return array
     */
    public function definition()
    {
        /*
            $table->string('name');
            $table->string('salary');
            $table->string('exp');
            $table->unsignedInteger('dev');
            $table->unsignedInteger('collab');
            $table->string('qualification');
        */
        return [
            //
            'name' => 'Asset_'.$this->faker->randomDigitNotNull,
            'salary' => $this->faker->randomElement(['26k', '35k', '56k' ,'85k']),
            'exp' => $this->faker->randomElement(['0-2 Yrs', '2-4 Years', '5 Years' ,'7 Years']),
            'dev' => $this->faker->randomElement([6,0]),
            'collab' => $this->faker->randomElement([4, 14, 15 ,1]),
            'qualification' => 'B.E/B.tech/MCA/Mtech/MS(IT)/MSc(CS/IT)',
        ];
    }
}
