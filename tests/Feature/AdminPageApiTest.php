<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pages']);
});

test('an authorized administrator can create a page with JSON blocks', function () {
    $permission = Permission::factory()->create(['name' => 'pages.create']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/admin/pages', [
        'title' => 'About Us',
        'slug' => 'about-us',
        'status' => 'draft',
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Hello']]],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.slug', 'about-us')
        ->assertJsonPath('data.blocks.0.type', 'rich_text');

    expect($response->json('data.id'))->toBeUuid();
    $this->assertDatabaseHas('audit_logs', ['event' => 'page.created']);
});

test('an administrator without permission cannot create a page', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'About Us', 'slug' => 'about-us', 'status' => 'draft', 'blocks' => [],
    ])->assertForbidden();
});
