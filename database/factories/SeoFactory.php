<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Seo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seo>
 */
class SeoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seoable_type' => Page::class,
            'seoable_id' => Page::factory(),
            'meta_title' => fake()->sentence(6),
            'meta_description' => fake()->sentence(12),
            'focus_keyword' => fake()->word(),
            'robots_index' => true,
            'robots_follow' => true,
            'twitter_card' => 'summary_large_image',
            'score' => fake()->numberBetween(0, 100),
        ];
    }
}
