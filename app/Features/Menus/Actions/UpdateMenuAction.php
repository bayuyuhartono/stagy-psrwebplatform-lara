<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuService;
use App\Models\Menu;

class UpdateMenuAction
{
    public function __construct(private MenuService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Menu $menu, array $attributes): Menu
    {
        return $this->service->update($menu, $attributes);
    }
}
