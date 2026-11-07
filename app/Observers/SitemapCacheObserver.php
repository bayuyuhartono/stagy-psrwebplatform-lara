<?php

namespace App\Observers;

use App\Features\Seo\Services\SitemapService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class SitemapCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private SitemapService $sitemap) {}

    public function saved(Model $content): void
    {
        $this->sitemap->invalidate();
    }

    public function deleted(Model $content): void
    {
        $this->sitemap->invalidate();
    }

    public function restored(Model $content): void
    {
        $this->sitemap->invalidate();
    }
}
