<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function actingAsCmsTaxonomyUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('taxonomy viewers can open category and tag management', function () {
    $this->actingAs(actingAsCmsTaxonomyUser(['taxonomies.view']))
        ->get('/cms/taxonomies')
        ->assertSuccessful()
        ->assertSee('data-taxonomy-manager', escape: false)
        ->assertSee('Categories & Tags')
        ->assertDontSee('data-taxonomy-new', escape: false);
});

test('taxonomy creators receive creation controls', function () {
    $this->actingAs(actingAsCmsTaxonomyUser(['taxonomies.view', 'taxonomies.create']))
        ->get('/cms/taxonomies')
        ->assertSuccessful()
        ->assertSee('data-can-create="true"', escape: false)
        ->assertSee('data-taxonomy-new', escape: false);
});

test('taxonomy editors receive granular update and delete controls', function () {
    $this->actingAs(actingAsCmsTaxonomyUser(['taxonomies.view', 'taxonomies.update', 'taxonomies.delete']))
        ->get('/cms/taxonomies')
        ->assertSuccessful()
        ->assertSee('data-can-update="true"', escape: false)
        ->assertSee('data-can-delete="true"', escape: false);
});

test('taxonomy management requires view permission', function () {
    $this->actingAs(actingAsCmsTaxonomyUser(['taxonomies.create']))
        ->get('/cms/taxonomies')
        ->assertForbidden();
});
