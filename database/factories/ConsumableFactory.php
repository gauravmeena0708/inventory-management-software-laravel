<?php

namespace Database\Factories;

use App\Models\Consumable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consumable>
 */
class ConsumableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consumable::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $items = [
            'A4 Paper Rim', 'Ball Pen (Blue)', 'Ball Pen (Black)', 'Sticky Notes 3x3',
            'Permanent Marker', 'Whiteboard Marker', 'Stapler Pin Box (No. 10)',
            'Binder Clips 25mm', 'Hand Sanitizer 500ml', 'Tissue Paper Box',
            'Laser Toner Cartridge', 'Wireless Mouse', 'Cat6 Ethernet Cable 3m',
            'AA Alkaline Battery (4pk)', 'AAA Alkaline Battery (4pk)', 'Desk Organiser',
            'Correction Tape', 'Highlighter (Yellow)', 'File Folder (A4)', 'Notepad Spiral',
        ];

        $name = $this->faker->randomElement($items);
        $max = $this->faker->randomElement([50, 100, 200, 500]);
        $min = $this->faker->numberBetween(5, (int) ($max * 0.2));

        return [
            'name' => $name,
            'sku' => 'CON-' . strtoupper($this->faker->bothify('??-####')),
            'unit' => $this->faker->randomElement(['piece', 'box', 'packet', 'rim', 'bottle', 'set']),
            'in_stock' => $this->faker->numberBetween(10, 50),
            'min_quantity' => $min,
            'max_quantity' => $max,
        ];
    }

    /**
     * Indicate that the consumable is in low stock state.
     */
    public function lowStock(int $inStock = 5, int $min = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => $inStock,
            'min_quantity' => $min,
            'max_quantity' => max($min * 2, 50),
        ]);
    }

    /**
     * Indicate that the consumable is completely out of stock.
     */
    public function outOfStock(int $min = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => 0,
            'min_quantity' => $min,
        ]);
    }

    /**
     * Set a specific stock balance.
     */
    public function inStock(int $quantity = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => $quantity,
        ]);
    }
}
