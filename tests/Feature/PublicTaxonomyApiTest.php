<?php

use App\Models\Category;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);
test('public taxonomy endpoints return alphabetical values and usage counts', function () {
    $category = Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
    Category::factory()->create(['name' => 'Zulu', 'slug' => 'zulu']);
    $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    Post::factory()->create()->categories()->attach($category);
    Portfolio::factory()->create()->categories()->attach($category);
    Post::factory()->create()->tags()->attach($tag);
    $this->getJson('/api/v1/public/categories')->assertSuccessful()->assertJsonPath('data.0.slug', 'alpha')->assertJsonPath('data.0.posts_count', 1)->assertJsonPath('data.0.portfolios_count', 1);
    $this->getJson('/api/v1/public/tags')->assertSuccessful()->assertJsonPath('data.0.posts_count', 1);
});
