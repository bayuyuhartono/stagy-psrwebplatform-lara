<?php

namespace Database\Factories;

use App\Features\Menus\Enums\MenuItemTarget;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'page_id' => null,
            'label' => fake()->words(2, true),
            'type' => MenuItemType::Custom,
            'url' => '/'.fake()->slug(),
            'target' => MenuItemTarget::Self,
            'position' => 0,
        ];
    }
}
