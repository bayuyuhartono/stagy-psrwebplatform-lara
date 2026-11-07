<?php

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Models\FeatureFlag;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'integrations', 'enabled' => true]);
});

function actingAsIntegrationAdministrator(array $permissions): User
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

test('an authorized administrator can list integrations', function () {
    actingAsIntegrationAdministrator(['integrations.view']);
    Integration::factory()->create(['provider' => IntegrationProvider::GoogleAnalytics]);

    $this->getJson('/api/v1/admin/integrations')
        ->assertSuccessful()
        ->assertJsonPath('data.0.provider', 'google-analytics')
        ->assertJsonMissingPath('data.0.secret_config');
});

test('an authorized administrator can configure an integration', function () {
    actingAsIntegrationAdministrator(['integrations.update']);

    $this->putJson('/api/v1/admin/integrations/google-analytics', [
        'enabled' => true,
        'measurement_id' => 'G-ABC123XYZ',
    ])->assertSuccessful()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.config.measurement_id', 'G-ABC123XYZ');

    $integration = Integration::query()->where('provider', IntegrationProvider::GoogleAnalytics)->firstOrFail();
    expect($integration->enabled)->toBeTrue()
        ->and($integration->public_config)->toBe(['measurement_id' => 'G-ABC123XYZ']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'integration.updated']);
});

test('integration secrets are encrypted and never returned', function () {
    actingAsIntegrationAdministrator(['integrations.update']);

    $response = $this->putJson('/api/v1/admin/integrations/recaptcha', [
        'enabled' => true,
        'site_key' => 'public-site-key',
        'secret_key' => 'private-secret-key',
    ])->assertSuccessful()
        ->assertJsonPath('data.configured_secrets.0', 'secret_key')
        ->assertJsonMissingPath('data.secret_config')
        ->assertJsonMissing(['private-secret-key']);

    $integration = Integration::query()->where('provider', IntegrationProvider::Recaptcha)->firstOrFail();
    expect($integration->secret_config)->toBe(['secret_key' => 'private-secret-key'])
        ->and($integration->getRawOriginal('secret_config'))->not->toContain('private-secret-key')
        ->and($response->getContent())->not->toContain('private-secret-key');
});

test('an omitted secret is preserved on later updates', function () {
    actingAsIntegrationAdministrator(['integrations.update']);
    Integration::factory()->create([
        'provider' => IntegrationProvider::Recaptcha,
        'enabled' => true,
        'public_config' => ['site_key' => 'old-site-key'],
        'secret_config' => ['secret_key' => 'existing-secret'],
    ]);

    $this->putJson('/api/v1/admin/integrations/recaptcha', [
        'enabled' => true,
        'site_key' => 'new-site-key',
    ])->assertSuccessful();

    expect(Integration::query()->where('provider', IntegrationProvider::Recaptcha)->firstOrFail()->secret_config)
        ->toBe(['secret_key' => 'existing-secret']);
});

test('integration provider values are validated', function (string $provider, array $payload, string $field) {
    actingAsIntegrationAdministrator(['integrations.update']);

    $this->putJson("/api/v1/admin/integrations/{$provider}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'analytics measurement id' => ['google-analytics', ['enabled' => true, 'measurement_id' => 'invalid'], 'measurement_id'],
    'tag manager container id' => ['google-tag-manager', ['enabled' => true, 'container_id' => 'invalid'], 'container_id'],
    'meta pixel id' => ['meta-pixel', ['enabled' => true, 'pixel_id' => 'abc'], 'pixel_id'],
]);

test('integration administration requires permission', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/integrations')->assertForbidden();
    $this->putJson('/api/v1/admin/integrations/google-analytics', [
        'enabled' => false,
        'measurement_id' => null,
    ])->assertForbidden();
});

test('unknown integration providers are not routable', function () {
    actingAsIntegrationAdministrator(['integrations.update']);

    $this->putJson('/api/v1/admin/integrations/unknown', ['enabled' => false])->assertNotFound();
});
