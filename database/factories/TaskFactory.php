<?php

namespace Database\Factories;

use App\Models\FileRecord;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'assigned_to' => null,
            'file_id' => null,
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => 'pending',
            'due_date' => $this->faker->optional(0.8)->dateTimeBetween('now', '+30 days')?->format('Y-m-d'),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Mark the task as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Mark the task as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Mark the task as in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Associate the task with an assigned user.
     */
    public function withAssignedUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Associate the task with a file registry record.
     */
    public function withFile(?FileRecord $file = null): static
    {
        return $this->state(fn (array $attributes) => [
            'file_id' => $file?->id ?? FileRecord::factory(),
        ]);
    }
}
