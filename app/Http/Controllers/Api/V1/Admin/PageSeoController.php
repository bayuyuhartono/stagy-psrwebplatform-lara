<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Seo\Actions\UpdateSeoAction;
use App\Features\Seo\Repositories\SeoRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdatePageSeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Page;
use App\Models\Seo;
use Illuminate\Support\Facades\Gate;

class PageSeoController extends Controller
{
    public function show(Page $page, SeoRepository $seo): SeoResource
    {
        Gate::authorize('viewAny', Seo::class);

        return new SeoResource($seo->forPage($page));
    }

    public function update(UpdatePageSeoRequest $request, Page $page, UpdateSeoAction $update): SeoResource
    {
        return new SeoResource($update->handle($page, $request->validated()));
    }
}
