<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Portfolio::class);

        return view('cms.portfolio.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Portfolio::class);

        return view('cms.portfolio.editor');
    }

    public function edit(Portfolio $portfolio): View
    {
        Gate::authorize('update', $portfolio);

        return view('cms.portfolio.editor', compact('portfolio'));
    }
}
