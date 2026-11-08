<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'menus', 'enabled' => true]));

function actingAsCmsMenuUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('menu viewers can open the navigation manager without mutation controls', function () {
    $this->actingAs(actingAsCmsMenuUser(['menus.view']))->get('/cms/menus')->assertSuccessful()->assertSee('data-menu-manager', escape: false)->assertDontSee('+ New menu');
});

test('menu creators and editors receive the appropriate controls', function () {
    $this->actingAs(actingAsCmsMenuUser(['menus.view', 'menus.create', 'menus.update']))->get('/cms/menus')->assertSuccessful()->assertSee('+ New menu')->assertSee('+ Add item');
});

test('opening the menu manager requires view permission', function () {
    $this->actingAs(actingAsCmsMenuUser(['menus.update']))->get('/cms/menus')->assertForbidden();
});

test('menu manager respects its feature flag', function () {
    FeatureFlag::query()->where('name', 'menus')->update(['enabled' => false]);
    $this->actingAs(actingAsCmsMenuUser(['menus.view']))->get('/cms/menus')->assertNotFound();
});

test('menu locations are chosen from the locations the active theme renders', function () {
    config(['themes.active' => 'stagy']);

    $this->actingAs(actingAsCmsMenuUser(['menus.view']))->get('/cms/menus')
        ->assertSuccessful()
        ->assertSeeInOrder(['<option value="header">Header</option>', '<option value="footer-product">Footer · Product</option>', '<option value="footer-help">Footer · Help</option>'], escape: false);
});
