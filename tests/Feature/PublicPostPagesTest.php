<?php

use App\Features\Posts\Enums\PostType;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]));

test('the blog index lists only currently published blog posts', function () {
    Post::factory()->published()->create(['title' => 'Published Insight', 'type' => PostType::Blog]);
    Post::factory()->published()->create(['title' => 'Company Announcement', 'type' => PostType::News]);
    Post::factory()->create(['title' => 'Draft Insight', 'type' => PostType::Blog]);
    Post::factory()->published()->create(['title' => 'Future Insight', 'type' => PostType::Blog, 'published_at' => now()->addDay()]);

    $this->get('/blog')
        ->assertSuccessful()
        ->assertSee('Published Insight')
        ->assertDontSee('Company Announcement')
        ->assertDontSee('Draft Insight')
        ->assertDontSee('Future Insight');
});

test('the news index lists only currently published news posts', function () {
    Post::factory()->published()->create(['title' => 'Studio Announcement', 'type' => PostType::News]);
    Post::factory()->published()->create(['title' => 'Design Perspective', 'type' => PostType::Blog]);

    $this->get('/news')
        ->assertSuccessful()
        ->assertSee('Studio Announcement')
        ->assertDontSee('Design Perspective');
});

test('a published article renders its CMS content taxonomies author and SEO', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Designing Better Public Services',
        'slug' => 'designing-better-public-services',
        'type' => PostType::Blog,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Good services begin with careful listening.']]],
    ]);
    $post->categories()->attach(Category::factory()->create(['name' => 'Strategy']));
    $post->tags()->attach(Tag::factory()->create(['name' => 'Service Design']));
    Seo::factory()->for($post, 'seoable')->create(['meta_title' => 'Better Public Services']);

    $this->get('/blog/designing-better-public-services')
        ->assertSuccessful()
        ->assertSee('<title>Better Public Services</title>', escape: false)
        ->assertSee($post->author->name)
        ->assertSee('Strategy')
        ->assertSee('Service Design')
        ->assertSee('Good services begin with careful listening.');
});

test('article detail routes enforce type and publication visibility', function () {
    Post::factory()->published()->create(['slug' => 'news-only', 'type' => PostType::News]);
    Post::factory()->create(['slug' => 'draft-blog', 'type' => PostType::Blog]);
    Post::factory()->published()->create(['slug' => 'future-blog', 'type' => PostType::Blog, 'published_at' => now()->addDay()]);

    $this->get('/blog/news-only')->assertNotFound();
    $this->get('/blog/draft-blog')->assertNotFound();
    $this->get('/blog/future-blog')->assertNotFound();
});

test('public article routes respect the posts feature flag', function () {
    FeatureFlag::query()->where('name', 'posts')->update(['enabled' => false]);

    $this->get('/blog')->assertNotFound();
    $this->get('/news')->assertNotFound();
});
