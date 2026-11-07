<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploader_id' => User::factory(),
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'folder' => null,
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => fake()->numberBetween(1000, 1000000),
            'checksum' => fake()->unique()->sha256(),
            'alt_text' => fake()->sentence(3),
            'caption' => null,
            'metadata' => null,
        ];
    }
}
