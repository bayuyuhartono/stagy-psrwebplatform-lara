<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Seo\Actions\UpdateSeoAction;
use App\Features\Seo\Repositories\SeoRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdatePostSeoRequest;
use App\Http\Resources\Api\V1\SeoResource;
use App\Models\Post;
use App\Models\Seo;
use Illuminate\Support\Facades\Gate;

class PostSeoController extends Controller
{
    public function show(Post $post, SeoRepository $seo): SeoResource
    {
        Gate::authorize('viewAny', Seo::class);

        // Content without SEO yet gets the defaults an empty form starts from, rather than a 500.
        return new SeoResource($seo->forContent($post) ?? new Seo);
    }

    public function update(UpdatePostSeoRequest $request, Post $post, UpdateSeoAction $update): SeoResource
    {
        return new SeoResource($update->handle($post, $request->validated()));
    }
}
