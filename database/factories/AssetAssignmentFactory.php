<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Official;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetAssignmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'official_id' => Official::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'returned_at' => null,
            'return_recorded_by' => null,
            'source' => 'application',
            'condition_out' => 'Good / Working',
            'condition_in' => null,
            'remarks' => null,
        ];
    }

    /**
     * Indicate that the assignment has been returned/closed.
     */
    public function returned(?User $recordedBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'returned_at' => now(),
            'return_recorded_by' => $recordedBy?->id ?? User::factory(),
            'condition_in' => 'Good condition, no damage',
        ]);
    }
}
