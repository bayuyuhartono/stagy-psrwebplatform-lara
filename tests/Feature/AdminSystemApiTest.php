<?php

use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'system', 'enabled' => true]);
});

function actingAsSystemAdministrator(array $permissions): User
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

test('system settings expose safe runtime configuration', function () {
    actingAsSystemAdministrator(['system-settings.view']);
    EmailSetting::factory()->create(['enabled' => true]);

    $response = $this->getJson('/api/v1/admin/system/settings')
        ->assertSuccessful()
        ->assertJsonPath('data.application.name', config('app.name'))
        ->assertJsonPath('data.application.locale', config('app.locale'))
        ->assertJsonPath('data.runtime.php_version', PHP_VERSION)
        ->assertJsonPath('data.services.database_connection', config('database.default'))
        ->assertJsonPath('data.services.cache_store', config('cache.default'))
        ->assertJsonPath('data.services.queue_connection', config('queue.default'))
        ->assertJsonPath('data.email.enabled', true)
        ->assertJsonPath('data.email.configured', true);

    expect($response->getContent())
        ->not->toContain('secret-password')
        ->not->toContain('mailer@example.com')
        ->not->toContain((string) config('app.key'));
});

test('system health reports operational checks and queue counts', function () {
    actingAsSystemAdministrator(['system-health.view']);

    $this->getJson('/api/v1/admin/system/health')
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'ok')
        ->assertJsonPath('data.checks.database.status', 'ok')
        ->assertJsonPath('data.checks.cache.status', 'ok')
        ->assertJsonPath('data.checks.storage.status', 'ok')
        ->assertJsonPath('data.checks.storage.writable', true)
        ->assertJsonPath('data.checks.queue.status', 'ok')
        ->assertJsonPath('data.checks.queue.pending', 0)
        ->assertJsonPath('data.checks.queue.failed', 0)
        ->assertJsonPath('data.checks.email.status', 'warning')
        ->assertJsonStructure(['data' => ['checks' => ['database' => ['latency_ms']], 'checked_at']]);
});

test('system endpoints enforce granular permissions', function () {
    actingAsSystemAdministrator(['system-settings.view']);

    $this->getJson('/api/v1/admin/system/settings')->assertSuccessful();
    $this->getJson('/api/v1/admin/system/health')->assertForbidden();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/admin/system/settings')->assertForbidden();
});

test('system endpoints respect their feature flag', function () {
    actingAsSystemAdministrator(['system-settings.view', 'system-health.view']);
    FeatureFlag::query()->where('name', 'system')->update(['enabled' => false]);

    $this->getJson('/api/v1/admin/system/settings')->assertNotFound();
    $this->getJson('/api/v1/admin/system/health')->assertNotFound();
});
