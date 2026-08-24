<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Official;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'asset_type' => AssetType::LAPTOP,
            'status' => AssetStatus::IN_STOCK,
            'serial_number' => strtoupper($this->faker->bothify('SN-####-????')),
            'asset_tag' => strtoupper($this->faker->bothify('TAG-####')),
            'model_number' => strtoupper($this->faker->bothify('MOD-###')),
            'part_code' => strtoupper($this->faker->bothify('PART-###')),
            'currency' => 'INR',
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'purchase_cost' => $this->faker->randomFloat(2, 20000, 150000),
            'warranty_expiry' => now()->addMonths(12),
            'amc_end' => now()->addMonths(6),
            'description' => $this->faker->sentence(),
            'remarks' => null,
            'specifications' => [
                'processor' => 'Intel Core i7',
                'ram' => '16GB',
                'storage' => '512GB SSD',
            ],
        ];
    }

    /**
     * Indicate that the asset is a Desktop.
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => AssetType::DESKTOP,
        ]);
    }

    /**
     * Indicate that the asset is a Laptop.
     */
    public function laptop(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => AssetType::LAPTOP,
        ]);
    }

    /**
     * Indicate that the asset is a Server.
     */
    public function server(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => AssetType::SERVER,
        ]);
    }

    /**
     * Indicate that the asset is a Switch.
     */
    public function switch(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => AssetType::SWITCH,
        ]);
    }

    /**
     * Indicate that the asset is Storage.
     */
    public function storage(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset_type' => AssetType::STORAGE,
        ]);
    }

    /**
     * Indicate that the asset is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::IN_STOCK,
            'assigned_official_id' => null,
        ]);
    }

    /**
     * Indicate that the asset is in use.
     */
    public function inUse(?Official $official = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::IN_USE,
            'assigned_official_id' => $official?->id ?? Official::factory(),
        ]);
    }

    /**
     * Indicate that the asset is under maintenance.
     */
    public function underMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::UNDER_MAINTENANCE,
        ]);
    }

    /**
     * Indicate that the asset is decommissioned.
     */
    public function decommissioned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::DECOMMISSIONED,
            'assigned_official_id' => null,
        ]);
    }
}
