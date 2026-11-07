<?php

namespace App\Features\Taxonomies\Actions;

use App\Features\Taxonomies\Services\TaxonomyService;
use App\Models\Category;
use App\Models\Tag;

class DeleteTaxonomyAction
{
    public function __construct(private TaxonomyService $service) {}

    public function handle(Category|Tag $taxonomy): void
    {
        $this->service->delete($taxonomy);
    }
}
