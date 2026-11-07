<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Services\Repositories\ServiceRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(IndexServiceRequest $request, ServiceRepository $services): AnonymousResourceCollection
    {
        return ServiceResource::collection($services->paginatePublished($request->validated(), $request->integer('per_page', 15)));
    }

    public function show(string $slug, ServiceRepository $services): ServiceResource
    {
        return new ServiceResource($services->findPublishedBySlug($slug));
    }
}
