<?php

namespace App\Features\Pages\Repositories;

use App\Features\Pages\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Page::query()
            ->with(['author:id,name', 'seo'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['locale'] ?? null, fn ($query, string $locale) => $query->forLocale($locale))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereAny(['title', 'slug'], 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedBySlug(string $slug): Page
    {
        return Page::query()
            ->with(['seo.ogImage', 'translations' => $this->publishedTranslations(...)])
            ->forCurrentLocale()
            ->where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->where('published_at', '<=', now())
            ->firstOrFail();
    }

    public function findPublishedBySlugOrNull(string $slug): ?Page
    {
        return Page::query()
            ->with(['seo.ogImage', 'translations' => $this->publishedTranslations(...)])
            ->forCurrentLocale()
            ->where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->where('published_at', '<=', now())
            ->first();
    }

    /**
     * Restrict an eager-loaded translation group to pages visitors can open.
     *
     * @param  HasMany<Page, Page>  $query
     */
    private function publishedTranslations(HasMany $query): void
    {
        $query->where('status', PageStatus::Published)->where('published_at', '<=', now());
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Page
    {
        return Page::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Page $page, array $attributes): Page
    {
        $page->update($attributes);

        return $page->refresh();
    }
}
