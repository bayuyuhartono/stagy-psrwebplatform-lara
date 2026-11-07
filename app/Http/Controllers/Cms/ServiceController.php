<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Service::class);

        return view('cms.services.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('cms.services.editor');
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('cms.services.editor', compact('service'));
    }
}
