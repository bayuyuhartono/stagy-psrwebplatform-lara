<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('any signed-in user can open their profile without a permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/cms/profile')
        ->assertSuccessful()
        ->assertSee('data-password-form', escape: false)
        ->assertSee($user->email);
});

test('guests are sent to the login page', function () {
    $this->get('/cms/profile')->assertRedirect('/cms/login');
});

test('a signed-in CMS user can change their password from the browser session', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)->putJson('/api/v1/admin/profile/password', [
        'current_password' => 'old-password',
        'password' => 'New-Password-123!',
        'password_confirmation' => 'New-Password-123!',
    ])->assertNoContent();

    expect(Hash::check('New-Password-123!', $user->refresh()->password))->toBeTrue();
    $this->assertDatabaseHas('audit_logs', ['event' => 'user.password_changed']);
});

test('the password is kept when the current password is wrong', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)->putJson('/api/v1/admin/profile/password', [
        'current_password' => 'not-my-password',
        'password' => 'New-Password-123!',
        'password_confirmation' => 'New-Password-123!',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    expect(Hash::check('old-password', $user->refresh()->password))->toBeTrue();
});
