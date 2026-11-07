<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Menus\Repositories\MenuRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MenuResource;

class MenuController extends Controller
{
    public function show(string $location, MenuRepository $menus): MenuResource
    {
        return new MenuResource($menus->findActiveByLocation($location));
    }
}
