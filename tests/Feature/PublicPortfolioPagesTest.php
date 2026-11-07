<?php

use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Portfolio;
use App\Models\Seo;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'portfolio', 'enabled' => true]));

test('the portfolio index lists only currently published case studies', function () {
    Portfolio::factory()->published()->create(['title' => 'Published Platform']);
    Portfolio::factory()->create(['title' => 'Draft Platform']);
    Portfolio::factory()->published()->create(['title' => 'Future Platform', 'published_at' => now()->addDay()]);

    $this->get('/portfolio')
        ->assertSuccessful()
        ->assertSee('Proof is in the work')
        ->assertSee('Published Platform')
        ->assertDontSee('Draft Platform')
        ->assertDontSee('Future Platform');
});

test('a published portfolio project renders its CMS case study and SEO', function () {
    $project = Portfolio::factory()->published()->create([
        'title' => 'Civic Digital Platform',
        'slug' => 'civic-digital-platform',
        'client' => 'City Office',
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'A measurable transformation for citizens.']]],
    ]);
    $project->categories()->attach(Category::factory()->create(['name' => 'Public Sector']));
    $project->tags()->attach(Tag::factory()->create(['name' => 'Product Design']));
    Seo::factory()->for($project, 'seoable')->create(['meta_title' => 'Civic Platform Case Study']);

    $this->get('/portfolio/civic-digital-platform')
        ->assertSuccessful()
        ->assertSee('<title>Civic Platform Case Study</title>', escape: false)
        ->assertSee('City Office')
        ->assertSee('Public Sector')
        ->assertSee('Product Design')
        ->assertSee('A measurable transformation for citizens.');
});

test('draft and future portfolio projects cannot be opened publicly', function () {
    Portfolio::factory()->create(['slug' => 'draft-project']);
    Portfolio::factory()->published()->create(['slug' => 'future-project', 'published_at' => now()->addDay()]);

    $this->get('/portfolio/draft-project')->assertNotFound();
    $this->get('/portfolio/future-project')->assertNotFound();
});

test('portfolio website routes respect the portfolio feature flag', function () {
    FeatureFlag::query()->where('name', 'portfolio')->update(['enabled' => false]);

    $this->get('/portfolio')->assertNotFound();
});
