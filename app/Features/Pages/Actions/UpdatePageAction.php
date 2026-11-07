<?php

namespace App\Features\Pages\Actions;

use App\Features\Pages\Services\PageService;
use App\Models\Page;

class UpdatePageAction
{
    public function __construct(private PageService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Page $page, array $attributes): Page
    {
        return $this->service->update($page, $attributes);
    }
}
