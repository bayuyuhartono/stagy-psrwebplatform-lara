<?php

use App\Models\FeatureFlag;
use App\Models\Seo;
use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'services', 'enabled' => true]));

test('the public services index lists only currently published services', function () {
    Service::factory()->published()->create(['title' => 'Published Strategy']);
    Service::factory()->create(['title' => 'Draft Strategy']);
    Service::factory()->published()->create(['title' => 'Future Strategy', 'published_at' => now()->addDay()]);

    $this->get('/services')
        ->assertSuccessful()
        ->assertSee('Ideas become useful')
        ->assertSee('Published Strategy')
        ->assertDontSee('Draft Strategy')
        ->assertDontSee('Future Strategy');
});

test('a published service has a CMS driven detail page and SEO metadata', function () {
    $service = Service::factory()->published()->create([
        'title' => 'Digital Product Design',
        'slug' => 'digital-product-design',
        'excerpt' => 'Designing products that people understand and enjoy.',
        'blocks' => [['type' => 'feature_grid', 'data' => ['items' => [['title' => 'Research', 'description' => 'Understand the opportunity.', 'icon' => null]]]]],
    ]);
    Seo::factory()->for($service, 'seoable')->create(['meta_title' => 'Product Design Services', 'meta_description' => 'Product design description']);

    $this->get('/services/digital-product-design')
        ->assertSuccessful()
        ->assertSee('<title>Product Design Services</title>', escape: false)
        ->assertSee('Digital Product Design')
        ->assertSee('Research')
        ->assertSee('Understand the opportunity.');
});

test('draft and future services cannot be opened publicly', function () {
    Service::factory()->create(['slug' => 'draft-service']);
    Service::factory()->published()->create(['slug' => 'future-service', 'published_at' => now()->addDay()]);

    $this->get('/services/draft-service')->assertNotFound();
    $this->get('/services/future-service')->assertNotFound();
});

test('service website routes respect the services feature flag', function () {
    FeatureFlag::query()->where('name', 'services')->update(['enabled' => false]);

    $this->get('/services')->assertNotFound();
});
