<?php

namespace App\Features\Seo\Repositories;

use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SitemapRepository
{
    /** @return Collection<int, Page|Post|Service|Portfolio> */
    public function publishedContent(): Collection
    {
        return new Collection([
            ...$this->published(Page::class, ['locale'])->all(),
            ...$this->published(Post::class, ['type', 'locale'])->all(),
            ...$this->published(Service::class)->all(),
            ...$this->published(Portfolio::class)->all(),
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $extraColumns
     * @return Collection<int, Model>
     */
    private function published(string $model, array $extraColumns = []): Collection
    {
        return $model::query()
            ->select(['id', 'slug', 'updated_at', ...$extraColumns])
            ->with('seo:id,seoable_type,seoable_id,canonical_url')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereDoesntHave('seo', fn ($query) => $query->where('robots_index', false))
            ->get();
    }
}
