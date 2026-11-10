<?php

use App\Features\Posts\Enums\PostStatus;
use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => true]);
});

function postEditorUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('the post editor shows the publish checklist, preview and seo analysis', function () {
    $this->actingAs(postEditorUser(['posts.create', 'seo.view']))
        ->get('/cms/posts/create')
        ->assertSuccessful()
        ->assertSee('data-publish-checklist', escape: false)
        ->assertSee('data-post-preview', escape: false)
        ->assertSee('data-post-seo-checks', escape: false);
});

test('an editor can preview a draft as visitors would see it, never indexed', function () {
    $post = Post::factory()->create(['title' => 'Draf rahasia', 'status' => PostStatus::Draft, 'locale' => 'id']);

    $this->actingAs(postEditorUser(['posts.update', 'posts.update.any']))
        ->get("/cms/posts/{$post->id}/preview")
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Draf rahasia')
        ->assertSee('It is not public until it is published.')
        ->assertSee(route('cms.posts.edit', $post), escape: false);

    $this->get('/blog/'.$post->slug)->assertNotFound();
});

test('previewing needs permission to edit the post', function () {
    $post = Post::factory()->create(['status' => PostStatus::Draft]);

    $this->actingAs(postEditorUser(['posts.view']))->get("/cms/posts/{$post->id}/preview")->assertForbidden();
    auth()->logout();
    $this->get("/cms/posts/{$post->id}/preview")->assertRedirect('/cms/login');
});
