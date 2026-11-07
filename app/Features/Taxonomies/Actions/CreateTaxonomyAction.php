<?php

namespace App\Features\Taxonomies\Actions;

use App\Features\Taxonomies\Services\TaxonomyService;
use App\Models\Category;
use App\Models\Tag;

class CreateTaxonomyAction
{
    public function __construct(private TaxonomyService $service) {}

    /** @param class-string<Category|Tag> $modelClass @param array<string,mixed> $attributes */
    public function handle(string $modelClass, array $attributes): Category|Tag
    {
        return $this->service->create($modelClass, $attributes);
    }
}
