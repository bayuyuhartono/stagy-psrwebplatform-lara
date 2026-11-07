<?php

use App\Features\Posts\Enums\PostType;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Post;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'posts']);
});

test('public post listing only returns published content and supports type and category filters', function () {
    $category = Category::factory()->create(['slug' => 'company']);
    $news = Post::factory()->published()->create(['title' => 'Launch News', 'slug' => 'launch-news', 'type' => PostType::News]);
    $news->categories()->attach($category);
    Post::factory()->published()->create(['type' => PostType::Blog]);
    Post::factory()->create(['type' => PostType::News]);

    $this->getJson('/api/v1/public/posts?type=news&category=company&search=Launch')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'launch-news');
});

test('a published post is publicly available by slug while drafts and scheduled posts are hidden', function () {
    Post::factory()->published()->create(['slug' => 'available']);
    Post::factory()->create(['slug' => 'draft']);
    Post::factory()->published()->create(['slug' => 'scheduled', 'published_at' => now()->addDay()]);

    $this->getJson('/api/v1/public/posts/available')->assertSuccessful()->assertJsonPath('data.slug', 'available');
    $this->getJson('/api/v1/public/posts/draft')->assertNotFound();
    $this->getJson('/api/v1/public/posts/scheduled')->assertNotFound();
});
