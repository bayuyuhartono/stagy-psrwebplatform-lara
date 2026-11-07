<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function actingAsCmsAccessUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('user viewers can open user administration without the roles tab', function () {
    $this->actingAs(actingAsCmsAccessUser(['users.view']))->get('/cms/users-roles')->assertSuccessful()->assertSee('data-access-manager', escape: false)->assertSee('Users')->assertDontSee('data-access-roles-tab', escape: false);
});

test('role viewers can open role administration without the users tab', function () {
    $this->actingAs(actingAsCmsAccessUser(['roles.view']))->get('/cms/users-roles')->assertSuccessful()->assertSee('data-access-roles-tab', escape: false)->assertDontSee('data-access-users-tab', escape: false);
});

test('administrators receive granular user and role controls', function () {
    $permissions = ['users.view', 'users.create', 'users.update', 'users.delete', 'roles.view', 'roles.create', 'roles.update', 'roles.delete'];
    $this->actingAs(actingAsCmsAccessUser($permissions))->get('/cms/users-roles')->assertSuccessful()->assertSee('+ New user')->assertSee('+ New role')->assertSee('data-can-update-users="true"', escape: false)->assertSee('data-can-update-roles="true"', escape: false);
});

test('user and role administration requires at least one view permission', function () {
    $this->actingAs(actingAsCmsAccessUser(['users.update', 'roles.update']))->get('/cms/users-roles')->assertForbidden();
});
