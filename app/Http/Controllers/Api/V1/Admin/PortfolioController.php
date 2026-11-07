<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Portfolios\Actions\CreatePortfolioAction;
use App\Features\Portfolios\Actions\DeletePortfolioAction;
use App\Features\Portfolios\Actions\RestorePortfolioAction;
use App\Features\Portfolios\Actions\UpdatePortfolioAction;
use App\Features\Portfolios\Repositories\PortfolioRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPortfolioRequest;
use App\Http\Requests\Api\V1\Admin\StorePortfolioRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePortfolioRequest;
use App\Http\Resources\Api\V1\PortfolioResource;
use App\Models\Portfolio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PortfolioController extends Controller
{
    public function index(IndexPortfolioRequest $request, PortfolioRepository $items): AnonymousResourceCollection
    {
        return PortfolioResource::collection($items->paginateForAdmin($request->validated(), $request->integer('per_page', 15)));
    }

    public function store(StorePortfolioRequest $request, CreatePortfolioAction $create): PortfolioResource
    {
        return new PortfolioResource($create->handle([...$request->validated(), 'author_id' => $request->user()->getAuthIdentifier()]));
    }

    public function show(Portfolio $portfolio): PortfolioResource
    {
        Gate::authorize('view', $portfolio);

        return new PortfolioResource($portfolio->load(['author:id,name', 'coverImage', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage']));
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio, UpdatePortfolioAction $update): PortfolioResource
    {
        return new PortfolioResource($update->handle($portfolio, $request->validated()));
    }

    public function destroy(Portfolio $portfolio, DeletePortfolioAction $delete): Response
    {
        Gate::authorize('delete', $portfolio);
        $delete->handle($portfolio);

        return response()->noContent();
    }

    public function restore(Portfolio $portfolio, RestorePortfolioAction $restore): PortfolioResource
    {
        Gate::authorize('restore', $portfolio);

        return new PortfolioResource($restore->handle($portfolio));
    }
}
