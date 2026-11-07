<?php

namespace Database\Factories;

use App\Features\Services\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'excerpt' => fake()->paragraph(),
            'icon' => 'briefcase',
            'status' => ServiceStatus::Draft,
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => fake()->paragraph()]]],
            'position' => fake()->numberBetween(0, 20),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => ServiceStatus::Published, 'published_at' => now()]);
    }
}
