<?php

use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function actingAsSeoAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $administrator = User::factory()->create();
    $administrator->roles()->attach($role);
    Sanctum::actingAs($administrator);

    return $administrator;
}

test('an authorized administrator can update page SEO metadata', function () {
    actingAsSeoAdministrator(['seo.update']);
    $page = Page::factory()->create();
    $image = Media::factory()->create();
    $title = 'Laravel CMS Platform for Modern Company Websites';
    $description = str_repeat('A', 130);

    $response = $this->putJson("/api/v1/admin/pages/{$page->id}/seo", [
        'meta_title' => $title,
        'meta_description' => $description,
        'focus_keyword' => 'Laravel CMS',
        'canonical_url' => 'https://example.com/about',
        'robots_index' => true,
        'robots_follow' => true,
        'og_title' => $title,
        'og_description' => 'A social description for the company website.',
        'og_image_id' => $image->id,
        'twitter_card' => 'summary_large_image',
        'structured_data' => ['@type' => 'WebPage'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.focus_keyword', 'Laravel CMS')
        ->assertJsonPath('data.open_graph.image.id', $image->id)
        ->assertJsonPath('data.score', 100);
    $this->assertDatabaseHas('audit_logs', ['event' => 'seo.updated']);
});

test('SEO URLs and Twitter cards are validated', function () {
    actingAsSeoAdministrator(['seo.update']);
    $page = Page::factory()->create();

    $this->putJson("/api/v1/admin/pages/{$page->id}/seo", [
        'canonical_url' => 'javascript:alert(1)',
        'robots_index' => true,
        'robots_follow' => true,
        'twitter_card' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors(['canonical_url', 'twitter_card']);
});

test('SEO updates require permission', function () {
    Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->create();

    $this->putJson("/api/v1/admin/pages/{$page->id}/seo", [
        'robots_index' => true,
        'robots_follow' => true,
        'twitter_card' => 'summary',
    ])->assertForbidden();
});

test('published public pages expose their SEO metadata', function () {
    $page = Page::factory()->published()->create(['slug' => 'about']);
    $page->seo()->create([
        'meta_title' => 'About Our Company',
        'robots_index' => true,
        'robots_follow' => true,
        'twitter_card' => 'summary',
    ]);

    $this->getJson('/api/v1/public/pages/about')
        ->assertSuccessful()
        ->assertJsonPath('data.seo.meta_title', 'About Our Company')
        ->assertJsonPath('data.seo.robots.index', true);
});

test('SEO administration routes are hidden when disabled', function () {
    FeatureFlag::query()->where('name', 'seo')->update(['enabled' => false]);
    Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->create();

    $this->getJson("/api/v1/admin/pages/{$page->id}/seo")->assertNotFound();
});

test('the admin page list carries each page SEO score for the SEO manager', function () {
    actingAsSeoAdministrator(['pages.view']);
    $page = Page::factory()->create();
    Seo::factory()->for($page, 'seoable')->create(['score' => 65]);

    $this->getJson('/api/v1/admin/pages')
        ->assertSuccessful()
        ->assertJsonPath('data.0.seo.score', 65);
});
