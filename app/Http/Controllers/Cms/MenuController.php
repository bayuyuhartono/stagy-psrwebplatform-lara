<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', Menu::class);

        return view('cms.menus.index');
    }
}
