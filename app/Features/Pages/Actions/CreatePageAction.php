<?php

namespace App\Features\Pages\Actions;

use App\Features\Pages\Services\PageService;
use App\Models\Page;

class CreatePageAction
{
    public function __construct(private PageService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Page
    {
        return $this->service->create($attributes);
    }
}
