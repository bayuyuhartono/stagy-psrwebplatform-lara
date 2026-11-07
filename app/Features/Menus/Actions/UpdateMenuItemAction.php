<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuItemService;
use App\Models\MenuItem;

class UpdateMenuItemAction
{
    public function __construct(private MenuItemService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(MenuItem $item, array $attributes): MenuItem
    {
        return $this->service->update($item, $attributes);
    }
}
