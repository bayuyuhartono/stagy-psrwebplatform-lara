<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests can view the CMS login screen', function () {
    $this->get('/cms/login')
        ->assertSuccessful()
        ->assertSee('Sign in to your workspace');
});

test('a valid administrator can sign in to the CMS', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $this->post('/cms/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('cms.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('invalid CMS credentials are rejected', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $this->from('/cms/login')->post('/cms/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])->assertRedirect('/cms/login')->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('CMS users can securely sign out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/cms/logout')
        ->assertRedirect(route('cms.login'));

    $this->assertGuest();
});
