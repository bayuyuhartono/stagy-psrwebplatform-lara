<?php

namespace App\Features\Seo\Repositories;

use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;

class SeoRepository
{
    public function forPage(Page $page): ?Seo
    {
        return $this->forContent($page);
    }

    /** @param array<string, mixed> $attributes */
    public function updateOrCreateForPage(Page $page, array $attributes): Seo
    {
        return $this->updateOrCreateForContent($page, $attributes);
    }

    public function forContent(Page|Portfolio|Post|Service $content): ?Seo
    {
        return $content->seo()->with('ogImage')->first();
    }

    /** @param array<string, mixed> $attributes */
    public function updateOrCreateForContent(Page|Portfolio|Post|Service $content, array $attributes): Seo
    {
        $seo = $content->seo()->updateOrCreate([], $attributes);

        return $seo->refresh()->load('ogImage');
    }
}
