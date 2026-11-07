<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Taxonomies\Actions\CreateTaxonomyAction;
use App\Features\Taxonomies\Actions\DeleteTaxonomyAction;
use App\Features\Taxonomies\Actions\UpdateTaxonomyAction;
use App\Features\Taxonomies\Repositories\TaxonomyRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTagRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTagRequest;
use App\Http\Resources\Api\V1\TaxonomyResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index(TaxonomyRepository $taxonomies): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Tag::class);

        return TaxonomyResource::collection($taxonomies->paginate(Tag::class));
    }

    public function store(StoreTagRequest $request, CreateTaxonomyAction $create): TaxonomyResource
    {
        return new TaxonomyResource($create->handle(Tag::class, $request->validated()));
    }

    public function show(Tag $tag): TaxonomyResource
    {
        Gate::authorize('view', $tag);

        return new TaxonomyResource($tag->loadCount(['posts', 'portfolios']));
    }

    public function update(UpdateTagRequest $request, Tag $tag, UpdateTaxonomyAction $update): TaxonomyResource
    {
        return new TaxonomyResource($update->handle($tag, $request->validated()));
    }

    public function destroy(Tag $tag, DeleteTaxonomyAction $delete): Response
    {
        Gate::authorize('delete', $tag);
        $delete->handle($tag);

        return response()->noContent();
    }
}
