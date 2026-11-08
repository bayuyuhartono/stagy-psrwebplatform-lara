<?php

namespace App\Features\Seo\Repositories;

use App\Features\Settings\Repositories\FeatureFlagRepository;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SitemapRepository
{
    public function __construct(private FeatureFlagRepository $featureFlags) {}

    /**
     * Published content of every module that is switched on; a switched-off
     * module's public routes 404, so its URLs must not be listed.
     *
     * @return Collection<int, Page|Post|Service|Portfolio>
     */
    public function publishedContent(): Collection
    {
        $modules = [
            'pages' => fn (): Collection => $this->published(Page::class, ['locale', 'translation_group_id']),
            'posts' => fn (): Collection => $this->published(Post::class, ['type', 'locale', 'translation_group_id']),
            'services' => fn (): Collection => $this->published(Service::class),
            'portfolio' => fn (): Collection => $this->published(Portfolio::class),
        ];

        return new Collection(collect($modules)
            ->filter(fn (callable $query, string $feature): bool => $this->featureFlags->isEnabled($feature))
            ->flatMap(fn (callable $query): array => $query()->all())
            ->all());
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
