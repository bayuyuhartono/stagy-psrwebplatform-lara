<?php

namespace App\Features\Pages\Actions;

use App\Features\Pages\Services\PageService;
use App\Models\Page;

class RestorePageAction
{
    public function __construct(private PageService $service) {}

    public function handle(Page $page): Page
    {
        return $this->service->restore($page);
    }
}
