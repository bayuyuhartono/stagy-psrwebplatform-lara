<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Pages\Repositories\PageRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PageResource;

class PageController extends Controller
{
    public function show(string $slug, PageRepository $pages): PageResource
    {
        return new PageResource($pages->findPublishedBySlug($slug));
    }
}
