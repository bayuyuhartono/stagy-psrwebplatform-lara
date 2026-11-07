<?php

namespace App\Features\Posts\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Localization\Services\TranslationLinker;
use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Repositories\PostRepository;
use App\Models\Post;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function __construct(
        private PostRepository $posts,
        private AuditLogger $auditLogger,
        private TranslationLinker $translations,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Post
    {
        return DB::transaction(function () use ($attributes): Post {
            [$postAttributes, $categoryIds, $tagIds] = $this->extractTaxonomies($this->normalizePublication($this->translations->link(Post::class, $attributes)));
            $post = $this->posts->create($postAttributes);
            $post->categories()->sync($categoryIds);
            $post->tags()->sync($tagIds);
            $this->auditLogger->record('post.created', $post, newValues: $post->getAttributes());

            return $post->load(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Post $post, array $attributes): Post
    {
        return DB::transaction(function () use ($post, $attributes): Post {
            $oldValues = $post->getAttributes();
            [$postAttributes, $categoryIds, $tagIds] = $this->extractTaxonomies($this->normalizePublication($attributes, $post));
            $post = $this->posts->update($post, $postAttributes);
            if ($categoryIds !== null) {
                $post->categories()->sync($categoryIds);
            }
            if ($tagIds !== null) {
                $post->tags()->sync($tagIds);
            }
            $this->auditLogger->record('post.updated', $post, $oldValues, $post->getAttributes());

            return $post->load(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);
        });
    }

    public function delete(Post $post): void
    {
        $this->auditLogger->record('post.deleted', $post, oldValues: $post->getAttributes());
        $post->delete();
    }

    public function restore(Post $post): Post
    {
        $post->restore();
        $this->auditLogger->record('post.restored', $post, newValues: $post->getAttributes());

        return $post->load(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /** @param array<string, mixed> $attributes
     * @return array{array<string, mixed>, ?array<int, string>, ?array<int, string>}
     */
    private function extractTaxonomies(array $attributes): array
    {
        $categoryIds = Arr::pull($attributes, 'category_ids');
        $tagIds = Arr::pull($attributes, 'tag_ids');

        return [$attributes, $categoryIds, $tagIds];
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizePublication(array $attributes, ?Post $post = null): array
    {
        $status = $attributes['status'] ?? $post?->status?->value ?? PostStatus::Draft->value;
        if ($status === PostStatus::Published->value && empty($attributes['published_at']) && $post?->published_at === null) {
            $attributes['published_at'] = now();
        }
        if ($status === PostStatus::Draft->value) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }
}
