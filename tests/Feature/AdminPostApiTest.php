<?php

use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'posts']);
});

function actAsPostAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('an authorized administrator can create a news post with taxonomies and publish it', function () {
    actAsPostAdministrator(['posts.create']);
    $category = Category::factory()->create();
    $tag = Tag::factory()->create();

    $response = $this->postJson('/api/v1/admin/posts', [
        'title' => 'Company launches a product',
        'slug' => 'company-launches-product',
        'type' => 'news',
        'status' => 'published',
        'excerpt' => 'Product announcement',
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Full story']]],
        'category_ids' => [$category->id],
        'tag_ids' => [$tag->id],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.type', 'news')
        ->assertJsonPath('data.categories.0.slug', $category->slug)
        ->assertJsonPath('data.tags.0.slug', $tag->slug);
    expect($response->json('data.id'))->toBeUuid();
    expect($response->json('data.published_at'))->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['event' => 'post.created']);
});

test('updating taxonomies synchronizes relationships and drafting clears publication time', function () {
    // posts.update alone is author-scoped; this actor edits a post written by someone else.
    actAsPostAdministrator(['posts.update', 'posts.update.any']);
    $post = Post::factory()->published()->create();
    $oldCategory = Category::factory()->create();
    $newCategory = Category::factory()->create();
    $post->categories()->attach($oldCategory);

    $this->putJson("/api/v1/admin/posts/{$post->id}", [
        'status' => 'draft', 'category_ids' => [$newCategory->id],
    ])->assertSuccessful()->assertJsonPath('data.published_at', null);

    expect($post->categories()->pluck('categories.id')->all())->toBe([$newCategory->id]);
});

test('post input validates type and taxonomy identifiers', function () {
    actAsPostAdministrator(['posts.create']);

    $this->postJson('/api/v1/admin/posts', [
        'title' => 'Invalid', 'slug' => 'invalid', 'type' => 'press-release', 'status' => 'draft',
        'blocks' => [], 'category_ids' => ['not-a-uuid'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'category_ids.0']);
});

test('an administrator without permission cannot create a post', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/posts', [
        'title' => 'Denied', 'slug' => 'denied', 'type' => 'blog', 'status' => 'draft', 'blocks' => [],
    ])->assertForbidden();
});
