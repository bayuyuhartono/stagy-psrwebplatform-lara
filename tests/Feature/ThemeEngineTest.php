<?php

use App\Features\Themes\DataTransferObjects\ThemeManifest;
use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('the default theme is discovered and active', function () {
    $themes = app(ThemeManager::class);

    expect($themes->activeSlug())->toBe('default')
        ->and($themes->all())->toHaveKey('default')
        ->and($themes->active()->name)->toBe('Default')
        ->and($themes->active()->settings)->toHaveKeys([
            'show_announcement_bar', 'header_style', 'footer_tagline', 'show_footer_social',
        ]);
});

test('public views resolve from the active theme rather than the application views', function () {
    expect(view()->exists('public.home'))->toBeTrue()
        ->and(view()->exists('components.public.layout'))->toBeTrue();

    $resolved = view()->getFinder()->find('public.home');

    expect($resolved)->toContain('resources/themes/default/views');
});

test('stored theme settings override manifest defaults and unknown keys are dropped', function () {
    $themes = app(ThemeManager::class);
    $manifest = $themes->active();

    $merged = $themes->settingsFor($manifest, [
        'header_style' => 'solid',
        'not_a_real_setting' => 'ignored',
    ]);

    expect($merged['header_style'])->toBe('solid')
        ->and($merged['show_announcement_bar'])->toBeTrue()
        ->and($merged)->not->toHaveKey('not_a_real_setting');
});

test('a manifest missing required keys is rejected', function () {
    ThemeManifest::fromArray('broken', ['name' => 'Broken']);
})->throws(InvalidArgumentException::class);

test('theme settings drive the rendered layout', function () {
    FeatureFlag::factory()->create(['name' => 'pages']);
    WebsiteSetting::factory()->create([
        'group' => 'theme',
        'settings' => ['header_style' => 'solid', 'show_footer_social' => true, 'show_announcement_bar' => true],
        'is_public' => true,
    ]);
    Page::factory()->published()->create(['slug' => 'about']);

    $response = $this->get('/about')->assertSuccessful();

    // 'solid' swaps the overlay header for a sticky one.
    expect($response->getContent())->toContain('sticky bg-public-ink');
});

test('the admin themes endpoint exposes the manifest schema', function () {
    FeatureFlag::factory()->create(['name' => 'website-settings']);
    $permission = Permission::query()->create(['name' => 'website-settings.view', 'label' => 'View']);
    $role = Role::query()->create(['name' => 'admin', 'label' => 'Admin']);
    $role->permissions()->sync([$permission->getKey()]);
    $user = User::factory()->create();
    $user->roles()->sync([$role->getKey()]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/themes')
        ->assertSuccessful()
        ->assertJsonPath('data.active.slug', 'default')
        ->assertJsonPath('data.active.settings_schema.header_style.type', 'select')
        ->assertJsonPath('data.settings.header_style', 'overlay');
});
