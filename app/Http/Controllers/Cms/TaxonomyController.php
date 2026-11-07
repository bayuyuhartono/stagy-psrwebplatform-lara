<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaxonomyController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', Category::class);

        return view('cms.taxonomies.index');
    }
}
