<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Menus\Actions\CreateMenuItemAction;
use App\Features\Menus\Actions\DeleteMenuItemAction;
use App\Features\Menus\Actions\UpdateMenuItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMenuItemRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMenuItemRequest;
use App\Http\Resources\Api\V1\MenuItemResource;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MenuItemController extends Controller
{
    public function store(StoreMenuItemRequest $request, Menu $menu, CreateMenuItemAction $create): MenuItemResource
    {
        return new MenuItemResource($create->handle($menu, $request->validated()));
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $menuItem, UpdateMenuItemAction $update): MenuItemResource
    {
        return new MenuItemResource($update->handle($menuItem, $request->validated()));
    }

    public function destroy(Menu $menu, MenuItem $menuItem, DeleteMenuItemAction $delete): Response
    {
        Gate::authorize('update', $menu);
        $delete->handle($menuItem);

        return response()->noContent();
    }
}
