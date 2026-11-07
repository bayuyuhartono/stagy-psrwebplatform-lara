<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'services', 'enabled' => true]));

function actingAsCmsServiceUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('service viewers can open the CMS service listing', function () {
    $this->actingAs(actingAsCmsServiceUser(['services.view']))->get('/cms/services')->assertSuccessful()->assertSee('data-services-list', escape: false)->assertDontSee('New service');
});

test('service creators can open the content block editor', function () {
    $this->actingAs(actingAsCmsServiceUser(['services.create']))->get('/cms/services/create')->assertSuccessful()->assertSee('Create a new service')->assertSee('Content blocks');
});

test('editing services requires update permission', function () {
    $service = Service::factory()->create();
    $this->actingAs(actingAsCmsServiceUser(['services.view']))->get("/cms/services/{$service->id}/edit")->assertForbidden();
    $this->actingAs(actingAsCmsServiceUser(['services.update']))->get("/cms/services/{$service->id}/edit")->assertSuccessful();
});

test('service screens respect their feature flag', function () {
    FeatureFlag::query()->where('name', 'services')->update(['enabled' => false]);
    $this->actingAs(actingAsCmsServiceUser(['services.view']))->get('/cms/services')->assertNotFound();
});
