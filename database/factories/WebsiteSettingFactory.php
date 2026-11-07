<?php

namespace Database\Factories;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebsiteSetting>
 */
class WebsiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = fake()->unique()->randomElement(WebsiteSettingGroup::cases());

        return [
            'group' => $group,
            'settings' => $group->defaults(),
            'is_public' => true,
        ];
    }
}
