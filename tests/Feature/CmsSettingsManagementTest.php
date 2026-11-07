<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function actingAsCmsSettingsUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

test('website settings viewers can open only the website tab', function () {
    $this->actingAs(actingAsCmsSettingsUser(['website-settings.view']))->get('/cms/settings')->assertSuccessful()->assertSee('data-settings-manager', escape: false)->assertSee('General')->assertDontSee('data-system-panel', escape: false);
});

test('system viewers can open read only diagnostics', function () {
    $this->actingAs(actingAsCmsSettingsUser(['system-settings.view', 'system-health.view']))->get('/cms/settings')->assertSuccessful()->assertSee('data-system-panel', escape: false)->assertDontSee('data-website-panel', escape: false);
});

test('website editors receive save controls', function () {
    $this->actingAs(actingAsCmsSettingsUser(['website-settings.view', 'website-settings.update']))->get('/cms/settings')->assertSuccessful()->assertSee('data-can-update-website="true"', escape: false)->assertSee('Save general');
});

test('settings workspace requires a relevant view permission', function () {
    $this->actingAs(actingAsCmsSettingsUser(['website-settings.update']))->get('/cms/settings')->assertForbidden();
});

test('email viewers receive a credential safe email panel', function () {
    $this->actingAs(actingAsCmsSettingsUser(['email-settings.view']))->get('/cms/settings')->assertSuccessful()->assertSee('data-email-panel', escape: false)->assertSee('SMTP delivery')->assertDontSee('data-website-panel', escape: false);
});

test('integration viewers receive the provider workspace', function () {
    $this->actingAs(actingAsCmsSettingsUser(['integrations.view']))->get('/cms/settings')->assertSuccessful()->assertSee('data-integrations-panel', escape: false)->assertSee('Third-party integrations');
});

test('feature flag viewers receive a read only feature workspace', function () {
    $this->actingAs(actingAsCmsSettingsUser(['feature-flags.view']))
        ->get('/cms/settings')
        ->assertSuccessful()
        ->assertSee('data-feature-flags-panel', escape: false)
        ->assertSee('Feature availability')
        ->assertSee('data-can-update-feature-flags="false"', escape: false)
        ->assertDontSee('data-website-panel', escape: false);
});

test('feature flag editors receive toggle controls', function () {
    $this->actingAs(actingAsCmsSettingsUser(['feature-flags.view', 'feature-flags.update']))
        ->get('/cms/settings')
        ->assertSuccessful()
        ->assertSee('data-can-update-feature-flags="true"', escape: false);
});
