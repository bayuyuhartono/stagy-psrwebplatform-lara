<?php

use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'posts']);
});

test('a category archive lists only that category and excludes other posts', function () {
    $engineering = Category::query()->create(['name' => 'Engineering', 'slug' => 'engineering']);
    $design = Category::query()->create(['name' => 'Design', 'slug' => 'design']);

    $matching = Post::factory()->published()->create(['type' => 'blog', 'title' => 'Content models that last']);
    $matching->categories()->sync([$engineering->getKey()]);

    $other = Post::factory()->published()->create(['type' => 'blog', 'title' => 'A design systems retrospective']);
    $other->categories()->sync([$design->getKey()]);

    $this->get('/blog/category/engineering')
        ->assertSuccessful()
        ->assertSee('Content models that last')
        ->assertDontSee('A design systems retrospective')
        ->assertSee('Category: Engineering');
});

test('a tag archive lists only posts carrying that tag', function () {
    $tag = Tag::query()->create(['name' => 'Laravel', 'slug' => 'laravel']);

    $matching = Post::factory()->published()->create(['type' => 'blog', 'title' => 'Queues in practice']);
    $matching->tags()->sync([$tag->getKey()]);

    Post::factory()->published()->create(['type' => 'blog', 'title' => 'Unrelated writing']);

    $this->get('/blog/tag/laravel')
        ->assertSuccessful()
        ->assertSee('Queues in practice')
        ->assertDontSee('Unrelated writing');
});

test('archives are scoped to their post type', function () {
    $category = Category::query()->create(['name' => 'Company', 'slug' => 'company']);

    $newsPost = Post::factory()->published()->create(['type' => 'news', 'title' => 'A studio announcement']);
    $newsPost->categories()->sync([$category->getKey()]);

    $blogPost = Post::factory()->published()->create(['type' => 'blog', 'title' => 'A blog essay']);
    $blogPost->categories()->sync([$category->getKey()]);

    $this->get('/news/category/company')
        ->assertSuccessful()
        ->assertSee('A studio announcement')
        ->assertDontSee('A blog essay');
});

test('an unknown taxonomy 404s instead of rendering an empty archive', function () {
    $this->get('/blog/category/does-not-exist')->assertNotFound();
    $this->get('/blog/tag/does-not-exist')->assertNotFound();
});

test('archive routes are not swallowed by the post slug route', function () {
    $category = Category::query()->create(['name' => 'Engineering', 'slug' => 'engineering']);
    // A post whose slug collides with the archive path segment.
    Post::factory()->published()->create(['type' => 'blog', 'slug' => 'category', 'title' => 'Slug collision post']);

    $post = Post::factory()->published()->create(['type' => 'blog', 'title' => 'Real archived post']);
    $post->categories()->sync([$category->getKey()]);

    $this->get('/blog/category/engineering')
        ->assertSuccessful()
        ->assertSee('Real archived post')
        ->assertDontSee('Slug collision post');
});

test('a published post links to its categories and tags', function () {
    $category = Category::query()->create(['name' => 'Engineering', 'slug' => 'engineering']);
    $tag = Tag::query()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $post = Post::factory()->published()->create(['type' => 'blog', 'slug' => 'linked-post']);
    $post->categories()->sync([$category->getKey()]);
    $post->tags()->sync([$tag->getKey()]);

    $this->get('/blog/linked-post')
        ->assertSuccessful()
        ->assertSee(route('website.blog.category', 'engineering'), escape: false)
        ->assertSee(route('website.blog.tag', 'laravel'), escape: false);
});
