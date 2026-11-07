<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SeoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        Gate::authorize('viewAny', Seo::class);

        return view('cms.seo.index');
    }
}
