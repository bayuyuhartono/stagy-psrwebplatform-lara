<?php

use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Seo;
use App\Models\Service;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the public homepage renders a complete fallback experience', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee('We design digital products')
        ->assertSee('How we help')
        ->assertSee('Built to perform');
});

test('the public homepage uses published CMS content and SEO', function () {
    WebsiteSetting::factory()->create([
        'group' => 'general',
        'settings' => ['site_name' => 'Pesoros Studio', 'tagline' => 'Ideas made useful', 'locale' => 'en'],
    ]);
    $page = Page::factory()->published()->create([
        'slug' => 'home',
        'blocks' => [['type' => 'hero', 'data' => ['heading' => 'A CMS-powered headline', 'subheading' => 'Dynamic introduction']]],
    ]);
    Seo::factory()->for($page, 'seoable')->create(['meta_title' => 'Custom homepage title', 'meta_description' => 'Custom homepage description']);
    Service::factory()->published()->create(['title' => 'Experience Design']);
    Portfolio::factory()->published()->create(['title' => 'Flagship Platform']);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('<title>Custom homepage title</title>', escape: false)
        ->assertSee('A CMS-powered headline')
        ->assertSee('Experience Design')
        ->assertSee('Flagship Platform')
        ->assertSee('Pesoros Studio');
});

test('draft homepage content is never rendered publicly', function () {
    Page::factory()->create(['slug' => 'home', 'blocks' => [['type' => 'hero', 'data' => ['heading' => 'Private draft headline']]]]);

    $this->get('/')->assertSuccessful()->assertDontSee('Private draft headline');
});
