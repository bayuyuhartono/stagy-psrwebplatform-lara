<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Seo\Actions\UpdateSeoAction;
use App\Features\Seo\Repositories\SeoRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateServiceSeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Seo;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class ServiceSeoController extends Controller
{
    public function show(Service $service, SeoRepository $seo): SeoResource
    {
        Gate::authorize('viewAny', Seo::class);

        return new SeoResource($seo->forContent($service));
    }

    public function update(UpdateServiceSeoRequest $request, Service $service, UpdateSeoAction $update): SeoResource
    {
        return new SeoResource($update->handle($service, $request->validated()));
    }
}
