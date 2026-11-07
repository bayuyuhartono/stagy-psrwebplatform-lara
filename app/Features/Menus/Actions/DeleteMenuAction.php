<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuService;
use App\Models\Menu;

class DeleteMenuAction
{
    public function __construct(private MenuService $service) {}

    public function handle(Menu $menu): void
    {
        $this->service->delete($menu);
    }
}
