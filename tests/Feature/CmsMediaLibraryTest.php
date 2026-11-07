<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'media', 'enabled' => true]);
});

function actingAsCmsMediaUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('media viewers can open the responsive library without upload controls', function () {
    $user = actingAsCmsMediaUser(['media.view']);

    $this->actingAs($user)
        ->get('/cms/media')
        ->assertSuccessful()
        ->assertSee('Media Library')
        ->assertSee('data-media-library', escape: false)
        ->assertDontSee('Upload media');
});

test('media uploaders receive the upload workflow', function () {
    $user = actingAsCmsMediaUser(['media.view', 'media.upload']);

    $this->actingAs($user)
        ->get('/cms/media')
        ->assertSuccessful()
        ->assertSee('Upload media')
        ->assertSee('data-upload-form', escape: false);
});

test('users without media permission cannot open the library', function () {
    $this->actingAs(User::factory()->create())->get('/cms/media')->assertForbidden();
});

test('CMS media route respects the media feature flag', function () {
    FeatureFlag::query()->where('name', 'media')->update(['enabled' => false]);
    $user = actingAsCmsMediaUser(['media.view']);

    $this->actingAs($user)->get('/cms/media')->assertNotFound();
});
