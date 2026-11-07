<?php

namespace Database\Factories;

use App\Models\PricingAddon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingAddon>
 */
class PricingAddonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'price' => fake()->randomElement([2_500, 15_000, 100_000]),
            'unit' => '/ unit',
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
