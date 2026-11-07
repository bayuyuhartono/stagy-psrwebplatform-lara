<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Dashboard\Actions\GetDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DashboardResource;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(GetDashboardAction $dashboard): DashboardResource
    {
        Gate::authorize('dashboard.view');

        return new DashboardResource($dashboard->handle());
    }
}
