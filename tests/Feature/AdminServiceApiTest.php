<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'services']);
});

function actAsServiceAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('an authorized administrator can create and publish an ordered service', function () {
    actAsServiceAdministrator(['services.create']);

    $response = $this->postJson('/api/v1/admin/services', [
        'title' => 'Web Development',
        'slug' => 'web-development',
        'excerpt' => 'Modern web applications',
        'icon' => 'code',
        'status' => 'published',
        'position' => 2,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Our process']]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.slug', 'web-development')
        ->assertJsonPath('data.position', 2)
        ->assertJsonPath('data.icon', 'code');
    expect($response->json('data.id'))->toBeUuid();
    expect($response->json('data.published_at'))->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['event' => 'service.created']);
});

test('drafting a published service clears its publication time', function () {
    actAsServiceAdministrator(['services.update']);
    $service = Service::factory()->published()->create();

    $this->putJson("/api/v1/admin/services/{$service->id}", ['status' => 'draft'])
        ->assertSuccessful()->assertJsonPath('data.published_at', null);
});

test('service content blocks and ordering are validated', function () {
    actAsServiceAdministrator(['services.create']);

    $this->postJson('/api/v1/admin/services', [
        'title' => 'Invalid', 'slug' => 'invalid', 'status' => 'published', 'position' => -1,
        'blocks' => [['type' => 'unknown', 'data' => []]],
    ])->assertUnprocessable()->assertJsonValidationErrors(['position', 'blocks']);
});

test('an administrator without permission cannot create a service', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/services', [
        'title' => 'Denied', 'slug' => 'denied', 'status' => 'draft', 'blocks' => [],
    ])->assertForbidden();
});
