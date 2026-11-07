<?php

namespace Database\Factories;

use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
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
            'title' => fake()->sentence(5),
            'slug' => fake()->unique()->slug(),
            'type' => PostType::Blog,
            'status' => PostStatus::Draft,
            'excerpt' => fake()->paragraph(),
            'blocks' => [['type' => 'rich_text', 'data' => ['content' => fake()->paragraph()]]],
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => PostStatus::Published, 'published_at' => now()]);
    }
}
