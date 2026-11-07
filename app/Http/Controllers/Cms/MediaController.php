<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MediaController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        Gate::authorize('viewAny', Media::class);

        return view('cms.media.index');
    }
}
