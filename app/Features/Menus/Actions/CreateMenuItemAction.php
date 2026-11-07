<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuItemService;
use App\Models\Menu;
use App\Models\MenuItem;

class CreateMenuItemAction
{
    public function __construct(private MenuItemService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Menu $menu, array $attributes): MenuItem
    {
        return $this->service->create($menu, $attributes);
    }
}
