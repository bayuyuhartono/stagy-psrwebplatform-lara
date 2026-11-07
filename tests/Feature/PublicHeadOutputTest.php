<?php

use App\Models\FeatureFlag;
use App\Models\Integration;
use App\Models\Media;
use App\Models\Page;
use App\Models\Seo;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pages']);
});

test('no static robots.txt shadows the dynamic robots route', function () {
    // The HTTP test kernel dispatches straight to the router and never consults
    // public/, so only a filesystem assertion can catch a static file returning.
    expect(public_path('robots.txt'))->not->toBeFile();
});

test('a page renders its open graph image and structured data', function () {
    $media = Media::factory()->create(['disk' => 'public', 'path' => 'media/social.jpg', 'alt_text' => 'Team at work']);
    $page = Page::factory()->published()->create(['slug' => 'about']);
    Seo::factory()->for($page, 'seoable')->create([
        'og_image_id' => $media->getKey(),
        'structured_data' => ['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => 'About us'],
    ]);

    $response = $this->get('/about')->assertSuccessful();

    expect($response->getContent())
        ->toContain('property="og:image"')
        ->toContain($media->url)
        ->toContain('name="twitter:image"')
        ->toContain('content="Team at work"')
        ->toContain('<script type="application/ld+json">')
        ->toContain('"@type":"AboutPage"');
});

test('a page without an og image falls back to the website social image', function () {
    $media = Media::factory()->create(['disk' => 'public', 'path' => 'media/default-social.jpg']);
    WebsiteSetting::factory()->create([
        'group' => 'seo',
        'settings' => ['default_title' => null, 'default_description' => null, 'social_image_id' => $media->getKey()],
        'is_public' => true,
    ]);
    Page::factory()->published()->create(['slug' => 'about']);

    $this->get('/about')
        ->assertSuccessful()
        ->assertSee($media->url, escape: false);
});

test('structured data cannot break out of the json-ld script block', function () {
    $page = Page::factory()->published()->create(['slug' => 'about']);
    Seo::factory()->for($page, 'seoable')->create([
        'structured_data' => ['@type' => 'WebPage', 'name' => '</script><script>alert(1)</script>'],
    ]);

    $response = $this->get('/about')->assertSuccessful();

    expect($response->getContent())->not->toContain('</script><script>alert(1)');
});

test('enabled integrations inject their scripts into the public site', function () {
    Integration::factory()->create([
        'provider' => 'google-analytics',
        'enabled' => true,
        'public_config' => ['measurement_id' => 'G-ABC123'],
    ]);
    Integration::factory()->create([
        'provider' => 'google-tag-manager',
        'enabled' => true,
        'public_config' => ['container_id' => 'GTM-XYZ789'],
    ]);
    Integration::factory()->create([
        'provider' => 'google-search-console',
        'enabled' => true,
        'public_config' => ['verification_code' => 'verify-token-123'],
    ]);

    $response = $this->get('/')->assertSuccessful();

    expect($response->getContent())
        ->toContain('G-ABC123')
        ->toContain('googletagmanager.com/gtag/js')
        ->toContain('GTM-XYZ789')
        ->toContain('googletagmanager.com/ns.html')
        ->toContain('<meta name="google-site-verification" content="verify-token-123">');
});

test('disabled integrations inject nothing', function () {
    Integration::factory()->create([
        'provider' => 'meta-pixel',
        'enabled' => false,
        'public_config' => ['pixel_id' => '9998887776'],
    ]);

    $response = $this->get('/')->assertSuccessful();

    expect($response->getContent())
        ->not->toContain('9998887776')
        ->not->toContain('fbevents.js');
});

test('integration secrets never reach the rendered page', function () {
    Integration::factory()->create([
        'provider' => 'recaptcha',
        'enabled' => true,
        'public_config' => ['site_key' => 'public-site-key'],
        'secret_config' => ['secret_key' => 'super-secret-value'],
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertDontSee('super-secret-value');
});

test('custom scripts render raw in the head and body', function () {
    Integration::factory()->create([
        'provider' => 'custom-scripts',
        'enabled' => true,
        'public_config' => [
            'head_html' => '<meta name="custom-head-marker" content="yes">',
            'body_html' => '<div data-custom-body-marker></div>',
        ],
    ]);

    $response = $this->get('/')->assertSuccessful();

    expect($response->getContent())
        ->toContain('<meta name="custom-head-marker" content="yes">')
        ->toContain('<div data-custom-body-marker></div>');
});
