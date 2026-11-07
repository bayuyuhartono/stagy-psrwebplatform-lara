<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Pages\Actions\CreatePageAction;
use App\Features\Pages\Actions\DeletePageAction;
use App\Features\Pages\Actions\RestorePageAction;
use App\Features\Pages\Actions\UpdatePageAction;
use App\Features\Pages\Repositories\PageRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPageRequest;
use App\Http\Requests\Api\V1\Admin\StorePageRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePageRequest;
use App\Http\Resources\Api\V1\PageResource;
use App\Models\Page;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexPageRequest $request, PageRepository $pages): AnonymousResourceCollection
    {
        return PageResource::collection($pages->paginateForAdmin(
            $request->validated(),
            $request->integer('per_page', 15),
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePageRequest $request, CreatePageAction $create): PageResource
    {
        $page = $create->handle([
            ...$request->validated(),
            'author_id' => $request->user()->getAuthIdentifier(),
        ]);

        return new PageResource($page);
    }

    /**
     * Display the specified resource.
     */
    public function show(Page $page): PageResource
    {
        Gate::authorize('view', $page);

        return new PageResource($page);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page, UpdatePageAction $update): PageResource
    {
        return new PageResource($update->handle($page, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Page $page, DeletePageAction $delete): Response
    {
        Gate::authorize('delete', $page);
        $delete->handle($page);

        return response()->noContent();
    }

    public function restore(Page $page, RestorePageAction $restore): PageResource
    {
        Gate::authorize('restore', $page);

        return new PageResource($restore->handle($page));
    }
}
