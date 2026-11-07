<?php

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\FeatureFlag;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'website-settings', 'enabled' => true]);
});

test('public website settings expose only groups marked public', function () {
    WebsiteSetting::factory()->create([
        'group' => WebsiteSettingGroup::General,
        'settings' => [
            'site_name' => 'Pesoro Studio',
            'tagline' => null,
            'locale' => 'id_ID',
            'timezone' => 'Asia/Jakarta',
        ],
        'is_public' => true,
    ]);
    WebsiteSetting::factory()->create([
        'group' => WebsiteSettingGroup::Experience,
        'is_public' => false,
    ]);

    $this->getJson('/api/v1/public/website-settings')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.group', 'general')
        ->assertJsonPath('data.0.settings.site_name', 'Pesoro Studio');
});

test('public website settings respect their feature flag', function () {
    FeatureFlag::query()->where('name', 'website-settings')->update(['enabled' => false]);

    $this->getJson('/api/v1/public/website-settings')->assertNotFound();
});
