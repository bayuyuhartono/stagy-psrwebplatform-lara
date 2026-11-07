<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function actingAsUserAdministrator(array $permissions): User
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

test('an authorized administrator can create a user and assign roles', function () {
    actingAsUserAdministrator(['users.create']);
    $assignedRole = Role::factory()->create(['name' => 'editor']);

    $response = $this->postJson('/api/v1/admin/users', [
        'name' => 'Content Editor',
        'email' => 'editor@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
        'role_ids' => [$assignedRole->id],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.email', 'editor@example.com')
        ->assertJsonPath('data.roles.0.id', $assignedRole->id);
    $this->assertDatabaseHas('audit_logs', ['event' => 'user.created']);
});

test('an administrator cannot delete their own account', function () {
    $administrator = actingAsUserAdministrator(['users.delete']);

    $this->deleteJson("/api/v1/admin/users/{$administrator->id}")->assertForbidden();
});

test('a user can update their profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/admin/profile', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ])->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    expect($user->refresh()->email)->toBe('updated@example.com');
});

test('changing a password revokes all API tokens', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->putJson('/api/v1/admin/profile/password', [
        'current_password' => 'old-password',
        'password' => 'New-Password-123!',
        'password_confirmation' => 'New-Password-123!',
    ])->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
