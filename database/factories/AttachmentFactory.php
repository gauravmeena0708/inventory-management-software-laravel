<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attachable_type' => FileRecord::class,
            'attachable_id' => 1,
            'disk' => 'private',
            'path' => 'attachments/' . date('Y/m') . '/' . $this->faker->uuid() . '.pdf',
            'original_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 1048576),
            'checksum' => hash('sha256', $this->faker->sentence()),
            'uploaded_by' => User::factory(),
        ];
    }
}
