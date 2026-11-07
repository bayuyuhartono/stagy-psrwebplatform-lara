<?php

namespace Database\Seeders;

use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Features\Seo\Services\SitemapService;
use App\Features\Services\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Placeholder Services, Portfolio, and Posts so the public site renders with
 * content instead of empty states. Safe to run repeatedly — every record is
 * keyed on its slug.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->taxonomies(Category::class, ['Engineering', 'Design', 'Company']);
        $tags = $this->taxonomies(Tag::class, ['Laravel', 'Accessibility', 'Product']);

        $this->seedServices();
        $this->seedPortfolio();
        $this->seedPosts($categories, $tags);

        app(SitemapService::class)->invalidate();
    }

    /**
     * @param  class-string<Category|Tag>  $model
     * @param  list<string>  $names
     * @return array<string, Category|Tag>
     */
    private function taxonomies(string $model, array $names): array
    {
        $resolved = [];

        foreach ($names as $name) {
            $slug = str($name)->slug()->toString();
            $resolved[$slug] = $model::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        return $resolved;
    }

    private function seedServices(): void
    {
        $services = [
            ['Digital Strategy', 'Positioning, research, and roadmaps that give the work a clear direction.'],
            ['Experience Design', 'Interface and brand systems designed to outlive a single launch.'],
            ['Platform Engineering', 'Accessible, performant builds on foundations your team can maintain.'],
        ];

        foreach ($services as $position => [$title, $excerpt]) {
            Service::query()->firstOrCreate(
                ['slug' => str($title)->slug()->toString()],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'status' => ServiceStatus::Published,
                    'position' => $position,
                    'published_at' => now(),
                    'blocks' => [
                        ['type' => 'rich_text', 'data' => ['content' => "<p>{$excerpt} Replace this placeholder copy in the CMS under Services.</p>"]],
                    ],
                ],
            );
        }
    }

    private function seedPortfolio(): void
    {
        $projects = [
            ['Northwind Platform', 'Northwind', 'A booking platform rebuilt around a single content model.'],
            ['Atlas Design System', 'Atlas Group', 'A component library shared across four product teams.'],
            ['Harbour Annual Report', 'Harbour Trust', 'An interactive report replacing a static PDF.'],
        ];

        foreach ($projects as $position => [$title, $client, $excerpt]) {
            Portfolio::query()->firstOrCreate(
                ['slug' => str($title)->slug()->toString()],
                [
                    'title' => $title,
                    'client' => $client,
                    'excerpt' => $excerpt,
                    'status' => PortfolioStatus::Published,
                    'is_featured' => $position === 0,
                    'position' => $position,
                    'project_date' => now()->subMonths($position * 4 + 2),
                    'published_at' => now(),
                    'blocks' => [
                        ['type' => 'rich_text', 'data' => ['content' => "<p>{$excerpt} Replace this placeholder copy in the CMS under Portfolio.</p>"]],
                    ],
                ],
            );
        }
    }

    /**
     * @param  array<string, Category|Tag>  $categories
     * @param  array<string, Category|Tag>  $tags
     */
    private function seedPosts(array $categories, array $tags): void
    {
        $posts = [
            [PostType::Blog, 'Designing a content model that lasts', 'engineering', ['laravel', 'product']],
            [PostType::Blog, 'Accessibility is a build-time decision', 'design', ['accessibility']],
            [PostType::News, 'We have opened a second studio', 'company', ['product']],
        ];

        foreach ($posts as $offset => [$type, $title, $categorySlug, $tagSlugs]) {
            $post = Post::query()->firstOrCreate(
                ['slug' => str($title)->slug()->toString()],
                [
                    'title' => $title,
                    'type' => $type,
                    'status' => PostStatus::Published,
                    'excerpt' => 'Placeholder excerpt — replace this in the CMS under Posts.',
                    'published_at' => now()->subDays($offset * 7 + 1),
                    'blocks' => [
                        ['type' => 'rich_text', 'data' => ['content' => '<p>Placeholder article body. Replace this in the CMS under Posts.</p>']],
                    ],
                ],
            );

            $post->categories()->syncWithoutDetaching([$categories[$categorySlug]->getKey()]);
            $post->tags()->syncWithoutDetaching(
                collect($tagSlugs)->map(fn (string $slug) => $tags[$slug]->getKey())->all(),
            );
        }
    }
}
