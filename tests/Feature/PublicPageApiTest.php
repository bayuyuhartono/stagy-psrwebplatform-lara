<?php

use App\Models\FeatureFlag;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pages']);
});

test('the public API returns a published page by slug', function () {
    $page = Page::factory()->published()->create(['slug' => 'about-us']);

    $this->getJson('/api/v1/public/pages/about-us')
        ->assertSuccessful()
        ->assertJsonPath('data.id', $page->id)
        ->assertJsonPath('data.status', 'published');
});

test('the public API does not expose drafts', function () {
    Page::factory()->create(['slug' => 'draft-page']);

    $this->getJson('/api/v1/public/pages/draft-page')->assertNotFound();
});

test('the pages API is unavailable when its feature is disabled', function () {
    FeatureFlag::query()->where('name', 'pages')->update(['enabled' => false]);

    $this->getJson('/api/v1/public/pages/anything')->assertNotFound();
});
