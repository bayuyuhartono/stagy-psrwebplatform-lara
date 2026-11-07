<?php

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'website-settings', 'enabled' => true]);
});

function actingAsWebsiteSettingsAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $administrator = User::factory()->create();
    $administrator->roles()->attach($role);
    Sanctum::actingAs($administrator);

    return $administrator;
}

test('an authorized administrator can list website setting groups', function () {
    actingAsWebsiteSettingsAdministrator(['website-settings.view']);
    WebsiteSetting::factory()->create(['group' => WebsiteSettingGroup::General]);

    $this->getJson('/api/v1/admin/website-settings')
        ->assertSuccessful()
        ->assertJsonPath('data.0.group', 'general');
});

test('an authorized administrator can update a typed website setting group', function () {
    actingAsWebsiteSettingsAdministrator(['website-settings.update']);

    $this->putJson('/api/v1/admin/website-settings/general', [
        'site_name' => 'Pesoro Studio',
        'tagline' => 'Digital products that work.',
        'locale' => 'id_ID',
        'timezone' => 'Asia/Jakarta',
    ])->assertSuccessful()
        ->assertJsonPath('data.group', 'general')
        ->assertJsonPath('data.settings.site_name', 'Pesoro Studio');

    expect(WebsiteSetting::query()->where('group', WebsiteSettingGroup::General)->firstOrFail()->settings)
        ->toMatchArray(['site_name' => 'Pesoro Studio', 'timezone' => 'Asia/Jakarta']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'website_setting.updated']);
});

test('website setting values are validated for their group', function () {
    actingAsWebsiteSettingsAdministrator(['website-settings.update']);

    $this->putJson('/api/v1/admin/website-settings/general', [
        'site_name' => 'Pesoro Studio',
        'locale' => 'invalid-locale',
        'timezone' => 'Not/A_Timezone',
    ])->assertUnprocessable()->assertJsonValidationErrors(['locale', 'timezone']);

    $this->putJson('/api/v1/admin/website-settings/branding', [
        'primary_color' => 'red',
    ])->assertUnprocessable()->assertJsonValidationErrors(['primary_color']);
});

test('website settings require the appropriate permission', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/website-settings')->assertForbidden();
    $this->putJson('/api/v1/admin/website-settings/general', [
        'site_name' => 'Pesoro Studio',
        'tagline' => null,
        'locale' => 'id_ID',
        'timezone' => 'Asia/Jakarta',
    ])->assertForbidden();
});

test('unknown website setting groups are not routable', function () {
    actingAsWebsiteSettingsAdministrator(['website-settings.update']);

    $this->putJson('/api/v1/admin/website-settings/internal', [])->assertNotFound();
});
