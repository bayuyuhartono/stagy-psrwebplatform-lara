<?php

namespace App\Features\Search\Repositories;

use App\Features\Settings\Repositories\FeatureFlagRepository;
use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SearchRepository
{
    public function __construct(private FeatureFlagRepository $featureFlags) {}

    /** @return Collection<int, array<string, mixed>> */
    public function search(string $term, int $limit, Collection $permissions): Collection
    {
        return collect([
            'pages' => [Page::class, ['title', 'slug'], 'pages.view', 'pages'],
            'posts' => [Post::class, ['title', 'slug'], 'posts.view', 'posts'],
            'services' => [Service::class, ['title', 'slug'], 'services.view', 'services'],
            'portfolio' => [Portfolio::class, ['title', 'slug'], 'portfolio.view', 'portfolio'],
            'media' => [Media::class, ['original_name', 'alt_text'], 'media.view', 'media'],
            'contact_messages' => [ContactMessage::class, ['subject', 'name'], 'contact-messages.view', 'contact-messages'],
            'users' => [User::class, ['name', 'email'], 'users.view', null],
        ])
            // Results from a switched-off module would link to CMS screens that 404.
            ->filter(fn (array $definition): bool => $permissions->contains($definition[2]) && ($definition[3] === null || $this->featureFlags->isEnabled($definition[3])))
            ->flatMap(fn (array $definition, string $type): Collection => $this->searchType($type, $definition[0], $definition[1], $term, $limit))
            ->sortByDesc('updated_at')
            ->take($limit)
            ->values();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, string>  $columns
     * @return Collection<int, array<string, mixed>>
     */
    private function searchType(string $type, string $modelClass, array $columns, string $term, int $limit): Collection
    {
        return $modelClass::query()
            ->whereAny($columns, 'like', "%{$term}%")
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Model $model): array => $this->result($type, $model));
    }

    /** @return array<string, mixed> */
    private function result(string $type, Model $model): array
    {
        $title = match ($type) {
            'media' => $model->getAttribute('original_name'),
            'contact_messages' => $model->getAttribute('subject') ?: $model->getAttribute('name'),
            default => $model->getAttribute('title') ?: $model->getAttribute('name'),
        };
        $subtitle = match ($type) {
            'media' => $model->getAttribute('alt_text'),
            'contact_messages' => $model->getAttribute('name'),
            'users' => $model->getAttribute('email'),
            default => $model->getAttribute('slug'),
        };
        $status = $model->getAttribute('status');

        return [
            'type' => $type,
            'id' => $model->getKey(),
            'title' => $title,
            'subtitle' => $subtitle,
            'status' => $status instanceof BackedEnum ? $status->value : $status,
            'updated_at' => $model->getAttribute('updated_at')?->toISOString(),
        ];
    }
}
