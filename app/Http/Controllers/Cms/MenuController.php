<?php

namespace App\Http\Controllers\Cms;

use App\Features\Themes\Services\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __invoke(ThemeManager $themes): View
    {
        Gate::authorize('viewAny', Menu::class);

        return view('cms.menus.index', [
            'menuLocations' => $themes->active()->menuLocations,
        ]);
    }
}
