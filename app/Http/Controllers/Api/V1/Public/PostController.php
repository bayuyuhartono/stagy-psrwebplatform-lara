<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Posts\Repositories\PostRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexPostRequest;
use App\Http\Resources\Api\V1\PostResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(IndexPostRequest $request, PostRepository $posts): AnonymousResourceCollection
    {
        return PostResource::collection($posts->paginatePublished($request->validated(), $request->integer('per_page', 15)));
    }

    public function show(string $slug, PostRepository $posts): PostResource
    {
        return new PostResource($posts->findPublishedBySlug($slug));
    }
}
