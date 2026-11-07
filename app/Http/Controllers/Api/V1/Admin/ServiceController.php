<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Services\Actions\CreateServiceAction;
use App\Features\Services\Actions\DeleteServiceAction;
use App\Features\Services\Actions\RestoreServiceAction;
use App\Features\Services\Actions\UpdateServiceAction;
use App\Features\Services\Repositories\ServiceRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexServiceRequest;
use App\Http\Requests\Api\V1\Admin\StoreServiceRequest;
use App\Http\Requests\Api\V1\Admin\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    public function index(IndexServiceRequest $request, ServiceRepository $services): AnonymousResourceCollection
    {
        return ServiceResource::collection($services->paginateForAdmin($request->validated(), $request->integer('per_page', 15)));
    }

    public function store(StoreServiceRequest $request, CreateServiceAction $create): ServiceResource
    {
        return new ServiceResource($create->handle([...$request->validated(), 'author_id' => $request->user()->getAuthIdentifier()]));
    }

    public function show(Service $service): ServiceResource
    {
        Gate::authorize('view', $service);

        return new ServiceResource($service->load(['author:id,name', 'seo.ogImage']));
    }

    public function update(UpdateServiceRequest $request, Service $service, UpdateServiceAction $update): ServiceResource
    {
        return new ServiceResource($update->handle($service, $request->validated()));
    }

    public function destroy(Service $service, DeleteServiceAction $delete): Response
    {
        Gate::authorize('delete', $service);
        $delete->handle($service);

        return response()->noContent();
    }

    public function restore(Service $service, RestoreServiceAction $restore): ServiceResource
    {
        Gate::authorize('restore', $service);

        return new ServiceResource($restore->handle($service));
    }
}
