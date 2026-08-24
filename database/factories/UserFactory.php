<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'role' => UserRole::VIEWER,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an Administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    /**
     * Indicate that the user is an Inventory Manager.
     */
    public function inventoryManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::INVENTORY_MANAGER,
        ]);
    }

    /**
     * Indicate that the user is a Stock Operator.
     */
    public function stockOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::STOCK_OPERATOR,
        ]);
    }

    /**
     * Indicate that the user is a Finance Operator.
     */
    public function financeOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::FINANCE_OPERATOR,
        ]);
    }

    /**
     * Indicate that the user is a Viewer.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::VIEWER,
        ]);
    }

    /**
     * Indicate that the user is an Auditor.
     */
    public function auditor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::AUDITOR,
        ]);
    }
}
