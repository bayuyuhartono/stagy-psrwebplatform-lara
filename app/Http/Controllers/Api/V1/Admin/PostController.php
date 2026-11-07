<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Posts\Actions\CreatePostAction;
use App\Features\Posts\Actions\DeletePostAction;
use App\Features\Posts\Actions\RestorePostAction;
use App\Features\Posts\Actions\UpdatePostAction;
use App\Features\Posts\Repositories\PostRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPostRequest;
use App\Http\Requests\Api\V1\Admin\StorePostRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePostRequest;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(IndexPostRequest $request, PostRepository $posts): AnonymousResourceCollection
    {
        return PostResource::collection($posts->paginateForAdmin($request->validated(), $request->integer('per_page', 15)));
    }

    public function store(StorePostRequest $request, CreatePostAction $create): PostResource
    {
        return new PostResource($create->handle([...$request->validated(), 'author_id' => $request->user()->getAuthIdentifier()]));
    }

    public function show(Post $post): PostResource
    {
        Gate::authorize('view', $post);

        return new PostResource($post->load(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage', 'coverImage', 'translations']));
    }

    public function update(UpdatePostRequest $request, Post $post, UpdatePostAction $update): PostResource
    {
        return new PostResource($update->handle($post, $request->validated()));
    }

    public function destroy(Post $post, DeletePostAction $delete): Response
    {
        Gate::authorize('delete', $post);
        $delete->handle($post);

        return response()->noContent();
    }

    public function restore(Post $post, RestorePostAction $restore): PostResource
    {
        Gate::authorize('restore', $post);

        return new PostResource($restore->handle($post));
    }
}
