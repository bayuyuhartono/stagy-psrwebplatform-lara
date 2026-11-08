<?php

use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function actingAsSearchAdministrator(array $permissions): User
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

beforeEach(function () {
    foreach (['global-search', 'pages', 'posts'] as $feature) {
        FeatureFlag::factory()->create(['name' => $feature, 'enabled' => true]);
    }
});

test('global search returns matching records from permitted modules', function () {
    actingAsSearchAdministrator(['global-search.view', 'pages.view', 'posts.view']);
    $page = Page::factory()->create(['title' => 'Acme Landing Page']);
    $post = Post::factory()->create(['title' => 'Acme Product News']);
    Page::factory()->create(['title' => 'Unrelated page']);

    $response = $this->getJson('/api/v1/admin/search?q=Acme&limit=10')
        ->assertSuccessful()
        ->assertJsonPath('data.query', 'Acme')
        ->assertJsonPath('data.total', 2);

    expect(collect($response->json('data.results'))->pluck('id')->all())
        ->toContain($page->id, $post->id);
});

test('global search never returns records from modules the user cannot view', function () {
    actingAsSearchAdministrator(['global-search.view', 'pages.view']);
    Page::factory()->create(['title' => 'Restricted Match']);
    Post::factory()->create(['title' => 'Restricted Match']);

    $response = $this->getJson('/api/v1/admin/search?q=Restricted')->assertSuccessful();

    expect(collect($response->json('data.results'))->pluck('type')->unique()->all())->toBe(['pages']);
});

test('global search leaves out modules switched off for the project', function () {
    actingAsSearchAdministrator(['global-search.view', 'pages.view', 'posts.view']);
    FeatureFlag::query()->where('name', 'posts')->update(['enabled' => false]);
    Page::factory()->create(['title' => 'Switched Match']);
    Post::factory()->create(['title' => 'Switched Match']);

    $response = $this->getJson('/api/v1/admin/search?q=Switched')->assertSuccessful();

    expect(collect($response->json('data.results'))->pluck('type')->unique()->all())->toBe(['pages']);
});

test('global search validates its query and requires search permission', function () {
    actingAsSearchAdministrator(['global-search.view']);
    $this->getJson('/api/v1/admin/search?q=a')->assertUnprocessable()->assertJsonValidationErrors('q');

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/admin/search?q=valid')->assertForbidden();
});

test('global search is hidden when disabled', function () {
    FeatureFlag::query()->where('name', 'global-search')->update(['enabled' => false]);
    actingAsSearchAdministrator(['global-search.view']);

    $this->getJson('/api/v1/admin/search?q=valid')->assertNotFound();
});
