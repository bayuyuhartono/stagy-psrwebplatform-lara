<?php

use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Portfolio;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);
beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'portfolio']);
});
function actAsPortfolioAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}
test('an authorized administrator can publish a featured portfolio with taxonomies', function () {
    actAsPortfolioAdministrator(['portfolio.create']);
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();
    $response = $this->postJson('/api/v1/admin/portfolio', [
        'title' => 'Commerce Platform', 'slug' => 'commerce-platform', 'client' => 'Acme', 'project_date' => '2026-08-01',
        'external_url' => 'https://example.com', 'status' => 'published', 'is_featured' => true, 'position' => 1,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Case study']]], 'category_ids' => [$category->id], 'tag_ids' => [$tag->id],
    ]);
    $response->assertSuccessful()->assertJsonPath('data.is_featured', true)->assertJsonPath('data.categories.0.slug', $category->slug)->assertJsonPath('data.tags.0.slug', $tag->slug);
    expect($response->json('data.id'))->toBeUuid();
    expect($response->json('data.published_at'))->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['event' => 'portfolio.created']);
});
test('portfolio update synchronizes taxonomies and drafting clears publication', function () {
    actAsPortfolioAdministrator(['portfolio.update']);
    $portfolio = Portfolio::factory()->published()->create();
    $old = Category::factory()->create();
    $new = Category::factory()->create();
    $portfolio->categories()->attach($old);
    $this->putJson("/api/v1/admin/portfolio/{$portfolio->id}", ['status' => 'draft', 'category_ids' => [$new->id]])->assertSuccessful()->assertJsonPath('data.published_at', null);
    expect($portfolio->categories()->pluck('categories.id')->all())->toBe([$new->id]);
});
test('portfolio validates URLs blocks and taxonomy identifiers', function () {
    actAsPortfolioAdministrator(['portfolio.create']);
    $this->postJson('/api/v1/admin/portfolio', ['title' => 'Invalid', 'slug' => 'invalid', 'status' => 'draft', 'external_url' => 'javascript:bad', 'blocks' => [['type' => 'unknown', 'data' => []]], 'tag_ids' => ['bad']])
        ->assertUnprocessable()->assertJsonValidationErrors(['external_url', 'blocks', 'tag_ids.0']);
});
test('an administrator without permission cannot create a portfolio', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/v1/admin/portfolio', ['title' => 'Denied', 'slug' => 'denied', 'status' => 'draft', 'blocks' => []])->assertForbidden();
});
