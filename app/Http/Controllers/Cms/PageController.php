<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Page::class);

        return view('cms.pages.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Page::class);

        return view('cms.pages.editor');
    }

    public function edit(Page $page): View
    {
        Gate::authorize('update', $page);

        return view('cms.pages.editor', ['page' => $page]);
    }
}
