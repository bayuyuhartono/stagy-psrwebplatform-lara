<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Taxonomies\Actions\CreateTaxonomyAction;
use App\Features\Taxonomies\Actions\DeleteTaxonomyAction;
use App\Features\Taxonomies\Actions\UpdateTaxonomyAction;
use App\Features\Taxonomies\Repositories\TaxonomyRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\TaxonomyResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(TaxonomyRepository $taxonomies): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Category::class);

        return TaxonomyResource::collection($taxonomies->paginate(Category::class));
    }

    public function store(StoreCategoryRequest $request, CreateTaxonomyAction $create): TaxonomyResource
    {
        return new TaxonomyResource($create->handle(Category::class, $request->validated()));
    }

    public function show(Category $category): TaxonomyResource
    {
        Gate::authorize('view', $category);

        return new TaxonomyResource($category->loadCount(['posts', 'portfolios']));
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateTaxonomyAction $update): TaxonomyResource
    {
        return new TaxonomyResource($update->handle($category, $request->validated()));
    }

    public function destroy(Category $category, DeleteTaxonomyAction $delete): Response
    {
        Gate::authorize('delete', $category);
        $delete->handle($category);

        return response()->noContent();
    }
}
