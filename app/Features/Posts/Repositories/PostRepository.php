<?php

namespace App\Features\Posts\Repositories;

use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PostRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $filters */
    public function paginatePublished(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->where('status', PostStatus::Published)->where('published_at', '<=', now())
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage'])->latest('published_at')->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): Post
    {
        return Post::query()->where('slug', $slug)->where('status', PostStatus::Published)->where('published_at', '<=', now())
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage'])->firstOrFail();
    }

    public function findPublishedBySlugAndType(string $slug, PostType $type): Post
    {
        return Post::query()->where('slug', $slug)->where('type', $type)->where('status', PostStatus::Published)->where('published_at', '<=', now())
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage'])->firstOrFail();
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
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('slug', $slug)))
            ->when($filters['tag'] ?? null, fn ($query, $slug) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('slug', $slug)))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($searchQuery) => $searchQuery->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")));
    }
}
