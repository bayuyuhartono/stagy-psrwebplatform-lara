<?php

use App\Features\Posts\Enums\PostType;
use App\Features\Seo\Services\SitemapService;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\Seo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    foreach (['posts', 'pages', 'seo'] as $feature) {
        FeatureFlag::factory()->create(['name' => $feature, 'enabled' => true]);
    }
    Cache::forget(SitemapService::CACHE_KEY);
});

/**
 * The JSON-LD documents a response ships, keyed by @type.
 *
 * @return array<string, array<string, mixed>>
 */
function jsonLdDocuments(TestResponse $response): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

    return collect($matches[1])->map(fn (string $json): array => json_decode($json, true))->keyBy('@type')->all();
}

function seoUserWith(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => (Permission::query()->where('name', $name)->first() ?? Permission::factory()->create(['name' => $name]))->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('an article is described as an article with its cover as the share image', function () {
    $cover = Media::factory()->create(['alt_text' => 'Guests at the gate']);
    $post = Post::factory()->published()->create([
        'title' => 'Shorter Registration Queues',
        'slug' => 'shorter-registration-queues',
        'type' => PostType::Blog,
        'excerpt' => 'Most queues start long before the event day.',
        'cover_image_id' => $cover->id,
    ]);
    $post->categories()->attach(Category::factory()->create(['name' => 'Event Tips', 'slug' => 'event-tips']));

    $response = $this->get('/blog/shorter-registration-queues')->assertSuccessful();

    $response
        ->assertSee('<title>Shorter Registration Queues · ', escape: false)
        ->assertSee('<meta property="og:type" content="article">', escape: false)
        ->assertSee('<meta property="article:published_time" content="'.$post->published_at->toAtomString().'">', escape: false)
        ->assertSee('<meta property="article:section" content="Event Tips">', escape: false)
        ->assertSee('<meta property="og:image" content="'.$cover->url.'">', escape: false)
        ->assertSee('<meta property="og:image:alt" content="Guests at the gate">', escape: false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false)
        ->assertSee('<meta property="og:locale" content="id_ID">', escape: false);

    $documents = jsonLdDocuments($response);
    expect($documents['BlogPosting'])
        ->toMatchArray(['headline' => 'Shorter Registration Queues', 'image' => [$cover->url], 'articleSection' => 'Event Tips', 'author' => ['@type' => 'Person', 'name' => $post->author->name]])
        ->and(collect($documents['BreadcrumbList']['itemListElement'])->pluck('name')->slice(1)->values()->all())
        ->toBe(['Blog', 'Event Tips', 'Shorter Registration Queues']);
});

test('an article without any image uses the compact twitter card', function () {
    Post::factory()->published()->create(['slug' => 'no-image', 'type' => PostType::Blog, 'cover_image_id' => null]);

    $this->get('/blog/no-image')
        ->assertSuccessful()
        ->assertSee('<meta name="twitter:card" content="summary">', escape: false)
        ->assertDontSee('og:image', escape: false);
});

test('editor written structured data replaces the generated document of the same type', function () {
    $post = Post::factory()->published()->create(['slug' => 'manual-schema', 'type' => PostType::News]);
    Seo::factory()->for($post, 'seoable')->create(['structured_data' => ['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => 'Hand written']]);

    $documents = jsonLdDocuments($this->get('/news/manual-schema')->assertSuccessful());

    expect($documents['NewsArticle']['headline'])->toBe('Hand written')
        ->and($documents)->toHaveKey('BreadcrumbList');
});

test('a page with FAQ blocks publishes FAQPage structured data', function () {
    Page::factory()->published()->create(['slug' => 'faq', 'blocks' => [
        ['type' => 'faq', 'data' => ['items' => [['question' => 'Can guests check in by phone?', 'answer' => 'Yes, with the QR code.']]]],
    ]]);

    $documents = jsonLdDocuments($this->get('/faq')->assertSuccessful());

    expect($documents['FAQPage']['mainEntity'])->toBe([
        ['@type' => 'Question', 'name' => 'Can guests check in by phone?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes, with the QR code.']],
    ]);
});

test('the home page describes the organization and website', function () {
    Page::factory()->published()->create(['slug' => 'home']);

    $documents = jsonLdDocuments($this->get('/')->assertSuccessful()->assertSee('<meta name="robots" content="index,follow">', escape: false));

    expect($documents)->toHaveKeys(['Organization', 'WebSite']);
});

test('a home page with no CMS content behind it is kept out of the index', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex,follow">', escape: false);
});

test('the not found page is noindex without a canonical or structured data', function () {
    $this->get('/missing-page')
        ->assertNotFound()
        ->assertSee('<meta name="robots" content="noindex,follow">', escape: false)
        ->assertDontSee('rel="canonical"', escape: false)
        ->assertDontSee('application/ld+json', escape: false);
});

test('later listing pages are their own canonical', function () {
    Post::factory()->published()->count(11)->create(['type' => PostType::Blog]);

    $this->get('/blog?page=2')
        ->assertSuccessful()
        ->assertSee('<link rel="canonical" href="'.url('/blog').'?page=2">', escape: false);
});

test('the sitemap links translated content to its language siblings', function () {
    $indonesian = Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en', 'translation_group_id' => $indonesian->translation_group_id]);
    Page::factory()->published()->create(['slug' => 'tentang', 'locale' => 'id']);

    $xml = $this->get('/sitemap.xml')->assertSuccessful()->getContent();

    expect($xml)
        ->toContain('<xhtml:link rel="alternate" hreflang="en" href="'.url('/en/pricing').'"/>')
        ->toContain('<xhtml:link rel="alternate" hreflang="id" href="'.url('/harga').'"/>')
        ->toContain('<xhtml:link rel="alternate" hreflang="x-default" href="'.url('/harga').'"/>')
        ->and(substr_count($xml, 'hreflang="x-default"'))->toBe(2);
});

test('reading SEO for content that has none returns the defaults', function () {
    $post = Post::factory()->create();

    $this->actingAs(seoUserWith(['seo.view']))
        ->getJson("/api/v1/admin/posts/{$post->id}/seo")
        ->assertSuccessful()
        ->assertJsonPath('data.id', null)
        ->assertJsonPath('data.robots.index', true);
});

test('the post editor shows the search panel only to SEO viewers', function () {
    $post = Post::factory()->create();

    $this->actingAs(seoUserWith(['posts.update', 'posts.update.any', 'seo.view', 'seo.update']))
        ->get("/cms/posts/{$post->id}/edit")
        ->assertSuccessful()
        ->assertSee('data-post-seo', escape: false)
        ->assertSee('data-editable="true"', escape: false);

    $this->actingAs(seoUserWith(['posts.update', 'posts.update.any']))
        ->get("/cms/posts/{$post->id}/edit")
        ->assertSuccessful()
        ->assertDontSee('data-post-seo', escape: false);
});
