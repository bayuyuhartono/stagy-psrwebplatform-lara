<?php

namespace Database\Factories;

use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Portfolio>
 */
class PortfolioFactory extends Factory
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
            'cover_image_id' => null,
            'title' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'excerpt' => fake()->paragraph(),
            'client' => fake()->company(),
            'project_date' => fake()->date(),
            'external_url' => fake()->url(),
            'status' => PortfolioStatus::Draft,
            'is_featured' => false,
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => fake()->paragraph()]]],
            'position' => fake()->numberBetween(0, 20),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => PortfolioStatus::Published, 'published_at' => now()]);
    }
}
