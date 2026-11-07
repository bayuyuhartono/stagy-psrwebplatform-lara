<?php

namespace App\Features\Pages\Actions;

use App\Features\Pages\Services\PageService;
use App\Models\Page;

class DeletePageAction
{
    public function __construct(private PageService $service) {}

    public function handle(Page $page): void
    {
        $this->service->delete($page);
    }
}
