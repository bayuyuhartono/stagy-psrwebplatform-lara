<?php

namespace Database\Factories;

use App\Models\PricingComparisonRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingComparisonRow>
 */
class PricingComparisonRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_name' => 'Registrasi & undangan',
            'label' => fake()->sentence(3),
            'values' => [],
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
