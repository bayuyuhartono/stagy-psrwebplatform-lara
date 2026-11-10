<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
});

function actingAsCmsPostUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('post viewers can open the filtered CMS listing', function () {
    $user = actingAsCmsPostUser(['posts.view']);

    $this->actingAs($user)->get('/cms/posts')
        ->assertSuccessful()
        ->assertSee('Posts &amp; News', escape: false)
        ->assertSee('data-posts-list', escape: false)
        ->assertDontSee('New post');
});

test('post creators can open the shared content block editor', function () {
    $user = actingAsCmsPostUser(['posts.create']);

    $this->actingAs($user)->get('/cms/posts/create')
        ->assertSuccessful()
        ->assertSee('Create a new post')
        ->assertSee('Add block')
        ->assertSee('Categories');
});

test('only post editors can edit existing posts', function () {
    $post = Post::factory()->create();
    $viewer = actingAsCmsPostUser(['posts.view']);
    $this->actingAs($viewer)->get("/cms/posts/{$post->id}/edit")->assertForbidden();

    // posts.update alone is author-scoped, so editing someone else's post needs posts.update.any.
    $editor = actingAsCmsPostUser(['posts.update', 'posts.update.any']);
    $this->actingAs($editor)->get("/cms/posts/{$post->id}/edit")->assertSuccessful()->assertSee($post->id);
});

test('post routes respect the posts feature flag', function () {
    FeatureFlag::query()->where('name', 'posts')->update(['enabled' => false]);
    $user = actingAsCmsPostUser(['posts.view']);

    $this->actingAs($user)->get('/cms/posts')->assertNotFound();
});

test('post creators can discover content block schemas without page permission', function () {
    $user = actingAsCmsPostUser(['posts.create']);

    $this->actingAs($user)
        ->withHeader('Referer', url('/cms/posts/create'))
        ->getJson('/api/v1/admin/content-blocks/schema')
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['rich_text', 'hero', 'image', 'call_to_action', 'feature_grid']]);
});
