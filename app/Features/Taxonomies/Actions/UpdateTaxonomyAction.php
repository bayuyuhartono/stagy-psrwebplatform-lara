<?php

namespace App\Features\Taxonomies\Actions;

use App\Features\Taxonomies\Services\TaxonomyService;
use App\Models\Category;
use App\Models\Tag;

class UpdateTaxonomyAction
{
    public function __construct(private TaxonomyService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(Category|Tag $taxonomy, array $attributes): Category|Tag
    {
        return $this->service->update($taxonomy, $attributes);
    }
}
