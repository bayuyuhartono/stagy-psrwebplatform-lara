<?php

namespace App\Features\Posts\Repositories;

use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'coverImage'])->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $filters */
    public function paginatePublished(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publishedQuery($filters)
            ->when($filters['exclude_id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage', 'coverImage'])
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * The post to feature at the top of a listing: the newest one marked
     * featured, otherwise simply the newest.
     */
    public function findFeaturedPublished(PostType $type): ?Post
    {
        return $this->publishedQuery(['type' => $type->value])
            ->with(['author:id,name', 'categories:id,name,slug', 'coverImage'])
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->first();
    }

    /**
     * Other published posts to read next: those sharing a category first, then the newest.
     *
     * @return Collection<int, Post>
     */
    public function relatedTo(Post $post, int $limit = 3): Collection
    {
        $categoryIds = $post->categories->modelKeys();

        return $this->publishedQuery(['type' => $post->type->value])
            ->whereKeyNot($post->getKey())
            ->with(['categories:id,name,slug', 'coverImage'])
            ->withCount(['categories as shared_categories_count' => fn ($query) => $query->whereKey($categoryIds)])
            ->orderByDesc('shared_categories_count')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Published, current-locale posts matching the listing filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Post>
     */
    private function publishedQuery(array $filters): Builder
    {
        return $this->filteredQuery($filters)->forCurrentLocale()->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }

    public function findPublishedBySlug(string $slug): Post
    {
        return Post::query()->forCurrentLocale()->where('slug', $slug)->where('status', PostStatus::Published)->where('published_at', '<=', now())
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage', 'coverImage', 'translations' => $this->publishedTranslations(...)])->firstOrFail();
    }

    public function findPublishedBySlugAndType(string $slug, PostType $type): Post
    {
        return Post::query()->forCurrentLocale()->where('slug', $slug)->where('type', $type)->where('status', PostStatus::Published)->where('published_at', '<=', now())
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage', 'coverImage', 'translations' => $this->publishedTranslations(...)])->firstOrFail();
    }

    /**
     * Restrict an eager-loaded translation group to posts visitors can open.
     *
     * @param  HasMany<Post, Post>  $query
     */
    private function publishedTranslations(HasMany $query): void
    {
        $query->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Post
    {
        return Post::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Post $post, array $attributes): Post
    {
        $post->update($attributes);

        return $post->refresh();
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        return Post::query()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['locale'] ?? null, fn ($query, $locale) => $query->forLocale($locale))
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn ($query, $slug) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('slug', $slug)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($searchQuery) => $searchQuery->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")));
    }
}
