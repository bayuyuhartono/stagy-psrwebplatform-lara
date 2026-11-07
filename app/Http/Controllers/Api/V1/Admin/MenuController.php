<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Menus\Actions\CreateMenuAction;
use App\Features\Menus\Actions\DeleteMenuAction;
use App\Features\Menus\Actions\UpdateMenuAction;
use App\Features\Menus\Repositories\MenuRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMenuRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMenuRequest;
use App\Http\Resources\Api\V1\MenuResource;
use App\Models\Menu;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MenuController extends Controller
{
    public function index(MenuRepository $menus): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Menu::class);

        return MenuResource::collection($menus->paginate());
    }

    public function store(StoreMenuRequest $request, CreateMenuAction $create): MenuResource
    {
        return new MenuResource($create->handle($request->validated()));
    }

    public function show(Menu $menu, MenuRepository $menus): MenuResource
    {
        Gate::authorize('view', $menu);

        return new MenuResource($menus->loadTree($menu));
    }

    public function update(UpdateMenuRequest $request, Menu $menu, UpdateMenuAction $update): MenuResource
    {
        return new MenuResource($update->handle($menu, $request->validated()));
    }

    public function destroy(Menu $menu, DeleteMenuAction $delete): Response
    {
        Gate::authorize('delete', $menu);
        $delete->handle($menu);

        return response()->noContent();
    }
}
