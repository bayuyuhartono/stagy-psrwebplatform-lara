<?php

namespace App\Features\Seo\Actions;

use App\Features\Seo\Services\SeoService;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;

class UpdateSeoAction
{
    public function __construct(private SeoService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Page|Portfolio|Post|Service $content, array $attributes): Seo
    {
        return $this->service->updateForContent($content, $attributes);
    }
}
