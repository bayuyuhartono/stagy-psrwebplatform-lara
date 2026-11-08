<?php

use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => true]));

function actingAsCmsSeoAuditUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('SEO viewers can open the content metadata manager', function () {
    $this->actingAs(actingAsCmsSeoAuditUser(['seo.view', 'pages.view']))->get('/cms/seo')->assertSuccessful()->assertSee('data-seo-manager', escape: false)->assertSee('SEO management');
});

test('SEO update permission is exposed to editor controls', function () {
    $this->actingAs(actingAsCmsSeoAuditUser(['seo.view', 'seo.update']))->get('/cms/seo')->assertSuccessful()->assertSee('data-can-update="true"', escape: false);
});

test('SEO screen requires permission and respects its feature flag', function () {
    $user = actingAsCmsSeoAuditUser(['pages.view']);
    $this->actingAs($user)->get('/cms/seo')->assertForbidden();
    FeatureFlag::query()->where('name', 'seo')->update(['enabled' => false]);
    $this->actingAs(actingAsCmsSeoAuditUser(['seo.view']))->get('/cms/seo')->assertNotFound();
});

test('audit log viewers can open the redacted activity listing', function () {
    $this->actingAs(actingAsCmsSeoAuditUser(['audit-logs.view']))->get('/cms/audit-logs')->assertSuccessful()->assertSee('data-audit-log-list', escape: false)->assertSee('Activity history')->assertDontSee('old_values');
});

test('audit log screen requires permission', function () {
    $this->actingAs(actingAsCmsSeoAuditUser(['seo.view']))->get('/cms/audit-logs')->assertForbidden();
});

test('the SEO manager only offers content modules that are switched on', function () {
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'services', 'enabled' => false]);

    $this->actingAs(actingAsCmsSeoAuditUser(['seo.view', 'pages.view', 'services.view']))
        ->get('/cms/seo')
        ->assertSuccessful()
        ->assertSee('<option value="pages">', escape: false)
        ->assertDontSee('<option value="services">', escape: false);
});
