<?php

use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function actingAsCmsPageUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('page viewers can open the CMS page listing', function () {
    $user = actingAsCmsPageUser(['pages.view']);

    $this->actingAs($user)
        ->get('/cms/pages')
        ->assertSuccessful()
        ->assertSee('Website pages')
        ->assertSee('data-pages-list', escape: false)
        ->assertDontSee('New page');
});

test('page creators can open the dynamic content editor', function () {
    $user = actingAsCmsPageUser(['pages.create']);

    $this->actingAs($user)
        ->get('/cms/pages/create')
        ->assertSuccessful()
        ->assertSee('Create a new page')
        ->assertSee('Content blocks')
        ->assertSee('data-page-editor', escape: false);
});

test('only page editors can open an existing page editor', function () {
    $page = Page::factory()->create();
    $viewer = actingAsCmsPageUser(['pages.view']);

    $this->actingAs($viewer)->get("/cms/pages/{$page->id}/edit")->assertForbidden();

    $editor = actingAsCmsPageUser(['pages.update']);
    $this->actingAs($editor)
        ->get("/cms/pages/{$page->id}/edit")
        ->assertSuccessful()
        ->assertSee($page->id);
});

test('CMS page routes respect the pages feature flag', function () {
    FeatureFlag::query()->where('name', 'pages')->update(['enabled' => false]);
    $user = actingAsCmsPageUser(['pages.view']);

    $this->actingAs($user)->get('/cms/pages')->assertNotFound();
});

test('page listing API supports title search and status filters', function () {
    $user = actingAsCmsPageUser(['pages.view']);
    Page::factory()->published()->create(['title' => 'Acme About']);
    Page::factory()->create(['title' => 'Acme Draft']);
    Page::factory()->published()->create(['title' => 'Other Page']);

    $this->actingAs($user)
        ->withHeader('Referer', url('/cms/pages'))
        ->getJson('/api/v1/admin/pages?search=Acme&status=published')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Acme About');
});
