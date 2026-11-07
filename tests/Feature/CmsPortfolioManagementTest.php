<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Portfolio;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'portfolio', 'enabled' => true]));

function actingAsCmsPortfolioUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('portfolio viewers can open the CMS listing', function () {
    $this->actingAs(actingAsCmsPortfolioUser(['portfolio.view']))->get('/cms/portfolio')->assertSuccessful()->assertSee('data-portfolio-list', escape: false)->assertDontSee('New project');
});

test('portfolio creators can open the complete case study editor', function () {
    $this->actingAs(actingAsCmsPortfolioUser(['portfolio.create']))->get('/cms/portfolio/create')->assertSuccessful()->assertSee('Create a new project')->assertSee('Case-study blocks')->assertSee('Cover image');
});

test('editing portfolio requires update permission', function () {
    $portfolio = Portfolio::factory()->create();
    $this->actingAs(actingAsCmsPortfolioUser(['portfolio.view']))->get("/cms/portfolio/{$portfolio->id}/edit")->assertForbidden();
    $this->actingAs(actingAsCmsPortfolioUser(['portfolio.update']))->get("/cms/portfolio/{$portfolio->id}/edit")->assertSuccessful();
});

test('portfolio screens respect their feature flag', function () {
    FeatureFlag::query()->where('name', 'portfolio')->update(['enabled' => false]);
    $this->actingAs(actingAsCmsPortfolioUser(['portfolio.view']))->get('/cms/portfolio')->assertNotFound();
});
