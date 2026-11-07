<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', PricingPlan::class);

        return view('cms.pricing.index');
    }
}
