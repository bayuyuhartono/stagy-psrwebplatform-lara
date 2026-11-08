<?php

namespace App\Features\Seo\Services;

use App\Features\Posts\Enums\PostType;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the schema.org JSON-LD documents a public page ships with, so content
 * teams get rich results without writing JSON by hand.
 *
 * Generated documents are skipped when the editor supplied structured data of
 * the same @type in the SEO panel; the editor's version always wins.
 */
class StructuredDataBuilder
{
    public function __construct(private PublicUrlGenerator $urls) {}

    /**
     * @param  array{site_name: string, home_url: string, canonical_url: string, title: string, description: ?string, image_url: ?string, logo_url: ?string, locale: string, is_home: bool, settings: Collection<string, mixed>}  $context
     * @return list<array<string, mixed>>
     */
    public function build(?Model $content, array $context): array
    {
        $manual = $this->manual($content);
        $generated = [];

        if ($context['is_home']) {
            $generated[] = $this->organization($context);
            $generated[] = $this->website($context);
        }

        if ($content instanceof Post) {
            $generated[] = $this->article($content, $context);
            $generated[] = $this->postBreadcrumbs($content, $context);
        }

        if ($content instanceof Page) {
            $generated[] = $this->faqPage($content);
        }

        $manualTypes = collect($manual)->pluck('@type')->flatten()->filter()->all();

        return collect($generated)
            ->filter()
            ->reject(fn (array $document): bool => in_array($document['@type'], $manualTypes, true))
            ->concat($manual)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function organization(array $context): array
    {
        $settings = $context['settings'];
        $contact = $settings->get('contact', []);
        $telephone = $contact['phone'] ?? $contact['whatsapp'] ?? null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $context['site_name'],
            'url' => $context['home_url'],
            'logo' => $context['logo_url'],
            'description' => $settings->get('general', [])['tagline'] ?? null,
            'email' => $contact['email'] ?? null,
            'sameAs' => array_values(array_filter($settings->get('social', []), fn (mixed $url): bool => is_string($url) && Str::startsWith($url, ['http://', 'https://']))) ?: null,
            'contactPoint' => filled($telephone) || filled($contact['email'] ?? null) ? array_filter([
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $telephone,
                'email' => $contact['email'] ?? null,
            ]) : null,
        ], filled(...));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function website(array $context): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $context['site_name'],
            'url' => $context['home_url'],
            'inLanguage' => $context['locale'],
            'description' => $context['description'],
        ], filled(...));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function article(Post $post, array $context): array
    {
        $publisher = array_filter([
            '@type' => 'Organization',
            'name' => $context['site_name'],
            'url' => $context['home_url'],
            'logo' => filled($context['logo_url']) ? ['@type' => 'ImageObject', 'url' => $context['logo_url']] : null,
        ]);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => $post->type === PostType::News ? 'NewsArticle' : 'BlogPosting',
            'headline' => Str::limit($post->title, 110, ''),
            'description' => $context['description'],
            'image' => filled($context['image_url']) ? [$context['image_url']] : null,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => ($post->updated_at ?? $post->published_at)?->toAtomString(),
            'author' => $post->relationLoaded('author') && $post->author !== null
                ? ['@type' => 'Person', 'name' => $post->author->name]
                : ['@type' => 'Organization', 'name' => $context['site_name']],
            'publisher' => $publisher,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $context['canonical_url']],
            'inLanguage' => $post->locale ?? $context['locale'],
            'articleSection' => $post->relationLoaded('categories') ? $post->categories->first()?->name : null,
            'keywords' => $post->relationLoaded('tags') && $post->tags->isNotEmpty() ? $post->tags->pluck('name')->implode(', ') : null,
        ], filled(...));
    }

    /**
     * Home → listing → category → article, matching the breadcrumb themes print.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function postBreadcrumbs(Post $post, array $context): array
    {
        $listingRoute = 'website.'.$post->type->value;
        $category = $post->relationLoaded('categories') ? $post->categories->first() : null;
        $trail = [
            [$context['site_name'], $context['home_url']],
            [Str::headline($post->type->value), localized_route($listingRoute.'.index')],
        ];

        if ($category !== null) {
            $trail[] = [$category->name, localized_route($listingRoute.'.category', $category->slug)];
        }

        $trail[] = [$post->title, $this->urls->for($post)];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)->values()->map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ])->all(),
        ];
    }

    /**
     * Every question from the page's FAQ blocks, or null when it has none.
     *
     * @return array<string, mixed>|null
     */
    private function faqPage(Page $page): ?array
    {
        $questions = collect($page->blocks ?? [])
            ->where('type', 'faq')
            ->flatMap(fn (array $block): array => $block['data']['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
            ])
            ->values();

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->all(),
        ];
    }

    /**
     * The editor-written structured data: one document or a list of them.
     *
     * @return list<array<string, mixed>>
     */
    private function manual(?Model $content): array
    {
        $data = $content?->getAttribute('seo')?->structured_data;

        if (! is_array($data) || $data === []) {
            return [];
        }

        return array_is_list($data) ? array_values(array_filter($data, is_array(...))) : [$data];
    }
}
