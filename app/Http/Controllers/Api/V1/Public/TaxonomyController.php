<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Taxonomies\Repositories\TaxonomyRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TaxonomyResource;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaxonomyController extends Controller
{
    public function categories(TaxonomyRepository $taxonomies): AnonymousResourceCollection
    {
        return TaxonomyResource::collection($taxonomies->all(Category::class));
    }

    public function tags(TaxonomyRepository $taxonomies): AnonymousResourceCollection
    {
        return TaxonomyResource::collection($taxonomies->all(Tag::class));
    }
}
