<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Portfolios\Repositories\PortfolioRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexPortfolioRequest;
use App\Http\Resources\Api\V1\PortfolioResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortfolioController extends Controller
{
    public function index(IndexPortfolioRequest $request, PortfolioRepository $items): AnonymousResourceCollection
    {
        return PortfolioResource::collection($items->paginatePublished($request->validated(), $request->integer('per_page', 15)));
    }

    public function show(string $slug, PortfolioRepository $items): PortfolioResource
    {
        return new PortfolioResource($items->findPublishedBySlug($slug));
    }
}
