<?php

namespace Database\Factories;

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->unique()->randomElement(IntegrationProvider::cases());

        return [
            'provider' => $provider,
            'enabled' => false,
            'public_config' => $provider->defaultPublicConfig(),
            'secret_config' => null,
        ];
    }
}
