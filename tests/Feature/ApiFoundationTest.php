<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('an administrator can create and revoke an API token', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $login = $this->postJson('/api/v1/admin/tokens', [
        'email' => $user->email,
        'password' => 'secret-password',
        'device_name' => 'test-suite',
    ]);

    $login->assertSuccessful()->assertJsonPath('data.token_type', 'Bearer');
    expect($login->json('data.token'))->toBeString();

    $this->withToken($login->json('data.token'))
        ->deleteJson('/api/v1/admin/tokens/current')
        ->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

test('model identifiers are UUIDs', function () {
    expect(User::factory()->create()->id)->toBeUuid();
});
