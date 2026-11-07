<?php

namespace Database\Factories;

use App\Features\Pages\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
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
            'status' => PageStatus::Draft,
            'blocks' => [
                ['type' => 'rich_text', 'data' => ['content' => fake()->paragraph()]],
            ],
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
