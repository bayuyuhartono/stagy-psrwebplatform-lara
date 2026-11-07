<?php

namespace Database\Factories;

use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingPlan>
 */
class PricingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Professional', 'Enterprise']),
            'tagline' => '200 tamu termasuk · scan QR',
            'price' => fake()->randomElement([4_000_000, 8_100_000, 11_600_000]),
            'extra_guest_price' => 2_500,
            'features' => ['Sistem registrasi, QR & dashboard', 'Tim on-site'],
            'support_summary' => 'Tim on-site · 2 QR scanner',
            'is_highlighted' => false,
            'highlight_label' => null,
            'cta_label' => 'Pilih paket',
            'cta_url' => '/demo',
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
