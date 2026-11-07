<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'contact-messages', 'enabled' => true]));

function actingAsCmsContactUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('contact message viewers can open the inbox', function () {
    $this->actingAs(actingAsCmsContactUser(['contact-messages.view']))->get('/cms/messages')->assertSuccessful()->assertSee('data-contact-inbox', escape: false)->assertSee('Contact inbox');
});

test('contact message permissions are exposed to the inbox safely', function () {
    $this->actingAs(actingAsCmsContactUser(['contact-messages.view', 'contact-messages.update', 'contact-messages.delete']))->get('/cms/messages')->assertSuccessful()->assertSee('data-can-update="true"', escape: false)->assertSee('data-can-delete="true"', escape: false);
});

test('opening the contact inbox requires view permission', function () {
    $this->actingAs(actingAsCmsContactUser(['contact-messages.update']))->get('/cms/messages')->assertForbidden();
});

test('contact inbox respects its feature flag', function () {
    FeatureFlag::query()->where('name', 'contact-messages')->update(['enabled' => false]);
    $this->actingAs(actingAsCmsContactUser(['contact-messages.view']))->get('/cms/messages')->assertNotFound();
});
