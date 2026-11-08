<?php

namespace App\Features\Portfolios\Repositories;

use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Models\Portfolio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PortfolioRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)->with([...$this->relations(), 'seo'])->orderBy('position')->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $filters */
    public function paginatePublished(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)->where('status', PortfolioStatus::Published)->where('published_at', '<=', now())
            ->with([...$this->relations(), 'seo.ogImage'])->orderByDesc('is_featured')->orderBy('position')->latest('published_at')->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): Portfolio
    {
        return Portfolio::query()->where('slug', $slug)->where('status', PortfolioStatus::Published)->where('published_at', '<=', now())
            ->with([...$this->relations(), 'seo.ogImage'])->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Portfolio
    {
        return Portfolio::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Portfolio $portfolio, array $attributes): Portfolio
    {
        $portfolio->update($attributes);

        return $portfolio->refresh();
    }

    /** @param array<string, mixed> $filters */
    private function filtered(array $filters): Builder
    {
        return Portfolio::query()
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when(array_key_exists('featured', $filters), fn ($query) => $query->where('is_featured', $filters['featured']))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('categories', fn ($nested) => $nested->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn ($query, $slug) => $query->whereHas('tags', fn ($nested) => $nested->where('slug', $slug)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('title', 'like', "%{$search}%")->orWhere('client', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")));
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return ['author:id,name', 'coverImage', 'categories:id,name,slug', 'tags:id,name,slug'];
    }
}
