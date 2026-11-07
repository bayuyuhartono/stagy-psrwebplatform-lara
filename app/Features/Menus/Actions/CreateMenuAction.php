<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuService;
use App\Models\Menu;

class CreateMenuAction
{
    public function __construct(private MenuService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Menu
    {
        return $this->service->create($attributes);
    }
}
