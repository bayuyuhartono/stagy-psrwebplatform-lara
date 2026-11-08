<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests are redirected from the CMS dashboard', function () {
    $this->get('/cms')->assertRedirect(route('cms.login'));
});

test('authenticated users can view the responsive CMS shell', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/cms')
        ->assertSuccessful()
        ->assertSee('Pesoros CMS')
        ->assertSee('Here’s what’s happening today.')
        ->assertSee('data-sidebar', escape: false)
        ->assertSee('data-dashboard', escape: false);
});

test('the CMS session can authenticate against Sanctum API routes', function () {
    FeatureFlag::factory()->create(['name' => 'dashboard', 'enabled' => true]);
    $permission = Permission::factory()->create(['name' => 'dashboard.view']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->withHeader('Referer', url('/cms'))
        ->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.system.database', 'ok');
});

test('the sidebar hides modules switched off for the project', function () {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'services', 'enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get('/cms')
        ->assertSuccessful()
        ->assertSee('data-nav-section="posts"', escape: false)
        ->assertDontSee('data-nav-section="services"', escape: false)
        ->assertDontSee('data-nav-section="portfolio"', escape: false);
});

test('the dashboard only counts modules switched on for the project', function () {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'portfolio', 'enabled' => false]);

    $this->actingAs(User::factory()->create())
        ->get('/cms')
        ->assertSuccessful()
        ->assertSee('data-metric="posts"', escape: false)
        ->assertDontSee('data-metric="portfolio"', escape: false);
});
