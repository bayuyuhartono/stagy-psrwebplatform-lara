<?php

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Models\FeatureFlag;
use App\Models\Integration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'integrations', 'enabled' => true]);
});

test('public integrations expose only enabled providers and safe configuration', function () {
    Integration::factory()->create([
        'provider' => IntegrationProvider::Recaptcha,
        'enabled' => true,
        'public_config' => ['site_key' => 'public-site-key'],
        'secret_config' => ['secret_key' => 'private-secret-key'],
    ]);
    Integration::factory()->create([
        'provider' => IntegrationProvider::GoogleAnalytics,
        'enabled' => false,
        'public_config' => ['measurement_id' => 'G-DISABLED'],
    ]);

    $response = $this->getJson('/api/v1/public/integrations')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.provider', 'recaptcha')
        ->assertJsonPath('data.0.config.site_key', 'public-site-key')
        ->assertJsonMissingPath('data.0.id')
        ->assertJsonMissingPath('data.0.secret_config');

    expect($response->getContent())->not->toContain('private-secret-key');
});

test('public integrations respect their feature flag', function () {
    FeatureFlag::query()->where('name', 'integrations')->update(['enabled' => false]);

    $this->getJson('/api/v1/public/integrations')->assertNotFound();
});
