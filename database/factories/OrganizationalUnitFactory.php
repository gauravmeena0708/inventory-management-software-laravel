<?php

namespace Database\Factories;

use App\Models\OrganizationalUnit;
use App\Enums\OrganizationalUnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationalUnit>
 */
class OrganizationalUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = OrganizationalUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z0-9]{5,10}'),
            'name' => $this->faker->company(),
            'unit_type' => $this->faker->randomElement(OrganizationalUnitType::cases()),
            'depth' => 0,
            'is_active' => true,
        ];
    }
}
