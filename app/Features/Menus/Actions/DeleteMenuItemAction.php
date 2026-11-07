<?php

namespace App\Features\Menus\Actions;

use App\Features\Menus\Services\MenuItemService;
use App\Models\MenuItem;

class DeleteMenuItemAction
{
    public function __construct(private MenuItemService $service) {}

    public function handle(MenuItem $item): void
    {
        $this->service->delete($item);
    }
}
