<?php

use App\Features\Posts\Enums\PostType;
use App\Features\Seo\Services\SitemapService;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;
use App\Observers\SitemapCacheObserver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    foreach (['seo', 'pages', 'posts', 'services', 'portfolio'] as $feature) {
        FeatureFlag::factory()->create(['name' => $feature, 'enabled' => true]);
    }
    Cache::forget(SitemapService::CACHE_KEY);
});

test('sitemap contains only currently published indexable content', function () {
    Page::factory()->published()->create(['slug' => 'about']);
    Page::factory()->create(['slug' => 'draft-page']);
    Page::factory()->published()->create(['slug' => 'future-page', 'published_at' => now()->addDay()]);
    $noIndexPage = Page::factory()->published()->create(['slug' => 'private-page']);
    Seo::factory()->create(['seoable_type' => Page::class, 'seoable_id' => $noIndexPage->id, 'robots_index' => false]);
    Post::factory()->published()->create(['type' => PostType::Blog, 'slug' => 'first-post']);
    Post::factory()->published()->create(['type' => PostType::News, 'slug' => 'company-news']);
    Service::factory()->published()->create(['slug' => 'web-development']);
    Portfolio::factory()->published()->create(['slug' => 'client-project']);

    $response = $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect($response->getContent())
        ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">')
        ->toContain('<loc>'.url('/about').'</loc>')
        ->toContain('<loc>'.url('/blog/first-post').'</loc>')
        ->toContain('<loc>'.url('/news/company-news').'</loc>')
        ->toContain('<loc>'.url('/services/web-development').'</loc>')
        ->toContain('<loc>'.url('/portfolio/client-project').'</loc>')
        ->not->toContain('draft-page')
        ->not->toContain('future-page')
        ->not->toContain('private-page');
});

test('sitemap uses and safely escapes custom canonical URLs', function () {
    $page = Page::factory()->published()->create(['slug' => 'canonical-page']);
    Seo::factory()->create([
        'seoable_type' => Page::class,
        'seoable_id' => $page->id,
        'canonical_url' => 'https://example.com/content?a=1&b=2',
    ]);

    expect($this->get('/sitemap.xml')->assertSuccessful()->getContent())
        ->toContain('<loc>https://example.com/content?a=1&amp;b=2</loc>');
});

test('robots allows the website blocks APIs and advertises the sitemap', function () {
    $response = $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())
        ->toContain("User-agent: *\nAllow: /\nDisallow: /api/")
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

test('content and SEO observers invalidate the sitemap cache', function () {
    Cache::put(SitemapService::CACHE_KEY, '<stale/>', 3600);
    $page = Page::factory()->published()->create();

    app(SitemapCacheObserver::class)->saved($page);

    expect(Cache::has(SitemapService::CACHE_KEY))->toBeFalse();

    Cache::put(SitemapService::CACHE_KEY, '<stale/>', 3600);
    $seo = Seo::factory()->create(['seoable_type' => Page::class, 'seoable_id' => $page->id]);
    app(SitemapCacheObserver::class)->saved($seo);

    expect(Cache::has(SitemapService::CACHE_KEY))->toBeFalse();
});

test('public content responses include generated and custom canonical URLs', function () {
    Page::factory()->published()->create(['slug' => 'about-us']);
    $post = Post::factory()->published()->create(['slug' => 'canonical-post']);
    Seo::factory()->create([
        'seoable_type' => Post::class,
        'seoable_id' => $post->id,
        'canonical_url' => 'https://content.example.com/canonical-post',
    ]);

    $this->getJson('/api/v1/public/pages/about-us')
        ->assertSuccessful()
        ->assertJsonPath('data.canonical_url', url('/about-us'));
    $this->getJson('/api/v1/public/posts/canonical-post')
        ->assertSuccessful()
        ->assertJsonPath('data.canonical_url', 'https://content.example.com/canonical-post');
});

test('sitemap respects the SEO feature flag', function () {
    FeatureFlag::query()->where('name', 'seo')->update(['enabled' => false]);

    $this->get('/sitemap.xml')->assertNotFound();
});

test('sitemap leaves out modules switched off for the project', function () {
    FeatureFlag::query()->where('name', 'services')->update(['enabled' => false]);
    Service::factory()->published()->create(['slug' => 'hidden-service']);
    Page::factory()->published()->create(['slug' => 'about']);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee('<loc>'.url('/about').'</loc>', escape: false)
        ->assertDontSee('hidden-service');
});

test('switching a module on or off refreshes the cached sitemap', function () {
    Cache::put(SitemapService::CACHE_KEY, 'stale');

    FeatureFlag::query()->where('name', 'services')->first()->update(['enabled' => false]);

    expect(Cache::has(SitemapService::CACHE_KEY))->toBeFalse();
});
