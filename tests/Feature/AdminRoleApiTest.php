<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function actingAsRoleAdministrator(array $permissions): User
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

test('an authorized administrator can create a role with permissions', function () {
    actingAsRoleAdministrator(['roles.create']);
    $permission = Permission::factory()->create(['name' => 'pages.view']);

    $response = $this->postJson('/api/v1/admin/roles', [
        'name' => 'content-reviewer',
        'label' => 'Content Reviewer',
        'permission_ids' => [$permission->id],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'content-reviewer')
        ->assertJsonPath('data.permissions.0.id', $permission->id);
});

test('a role assigned to users cannot be deleted', function () {
    actingAsRoleAdministrator(['roles.delete']);
    $assignedRole = Role::factory()->create();
    User::factory()->create()->roles()->attach($assignedRole);

    $this->deleteJson("/api/v1/admin/roles/{$assignedRole->id}")->assertForbidden();
});

test('permissions can be listed but not mutated through the API', function () {
    actingAsRoleAdministrator(['roles.view']);
    Permission::factory()->create(['name' => 'pages.view']);

    $this->getJson('/api/v1/admin/permissions')
        ->assertSuccessful()
        ->assertJsonPath('data.0.name', 'pages.view');

    $this->postJson('/api/v1/admin/permissions', [])->assertMethodNotAllowed();
});
