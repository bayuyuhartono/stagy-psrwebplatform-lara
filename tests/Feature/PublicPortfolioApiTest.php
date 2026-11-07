<?php

use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Portfolio;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'portfolio']);
});
test('public portfolio supports featured category and search filters with configured order', function () {
    $category = Category::factory()->create(['slug' => 'web']);
    $match = Portfolio::factory()->published()->create(['title' => 'Featured Web Project', 'slug' => 'match', 'is_featured' => true, 'position' => 2]);
    $match->categories()->attach($category);
    Portfolio::factory()->published()->create(['title' => 'Other', 'is_featured' => false, 'position' => 1]);
    Portfolio::factory()->create(['title' => 'Featured Web Draft', 'is_featured' => true]);
    $this->getJson('/api/v1/public/portfolio?featured=1&category=web&search=Web')->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'match');
});
test('published portfolio detail is public while drafts and scheduled projects are hidden', function () {
    Portfolio::factory()->published()->create(['slug' => 'available']);
    Portfolio::factory()->create(['slug' => 'draft']);
    Portfolio::factory()->published()->create(['slug' => 'scheduled', 'published_at' => now()->addDay()]);
    $this->getJson('/api/v1/public/portfolio/available')->assertSuccessful();
    $this->getJson('/api/v1/public/portfolio/draft')->assertNotFound();
    $this->getJson('/api/v1/public/portfolio/scheduled')->assertNotFound();
});
