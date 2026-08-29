<?php

namespace Database\Factories;

use App\Enums\StockEntryType;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Official;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consumable_id' => Consumable::factory(),
            'type' => StockEntryType::PURCHASE,
            'quantity' => 10,
            'stock_after' => 10,
            'recipient_official_id' => null,
            'recorded_by' => User::factory(),
            'remarks' => $this->faker->sentence(),
            'idempotency_key' => null,
            'legacy_id' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the entry is a purchase.
     */
    public function purchase(int $quantity = 20, int $stockAfter = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockEntryType::PURCHASE,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'recipient_official_id' => null,
        ]);
    }

    /**
     * Indicate that the entry is an issue to an official.
     */
    public function issue(int $quantity = 5, int $stockAfter = 15, ?Official $recipient = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockEntryType::ISSUE,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'recipient_official_id' => $recipient?->id ?? Official::factory(),
        ]);
    }

    /**
     * Indicate that the entry is an inbound stock adjustment.
     */
    public function adjustmentIn(int $quantity = 5, int $stockAfter = 25): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockEntryType::ADJUSTMENT_IN,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'recipient_official_id' => null,
        ]);
    }

    /**
     * Indicate that the entry is an outbound stock adjustment.
     */
    public function adjustmentOut(int $quantity = 5, int $stockAfter = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockEntryType::ADJUSTMENT_OUT,
            'quantity' => $quantity,
            'stock_after' => $stockAfter,
            'recipient_official_id' => null,
        ]);
    }
}
