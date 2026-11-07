<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Seo\Actions\UpdateSeoAction;
use App\Features\Seo\Repositories\SeoRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdatePortfolioSeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Portfolio;
use App\Models\Seo;
use Illuminate\Support\Facades\Gate;

class PortfolioSeoController extends Controller
{
    public function show(Portfolio $portfolio, SeoRepository $seo): SeoResource
    {
        Gate::authorize('viewAny', Seo::class);

        return new SeoResource($seo->forContent($portfolio));
    }

    public function update(UpdatePortfolioSeoRequest $request, Portfolio $portfolio, UpdateSeoAction $update): SeoResource
    {
        return new SeoResource($update->handle($portfolio, $request->validated()));
    }
}
