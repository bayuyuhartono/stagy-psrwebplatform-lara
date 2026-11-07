<?php

use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'website-settings', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function actingAsSettingsEditor(): void
{
    $role = Role::factory()->create();
    $role->permissions()->attach([
        Permission::factory()->create(['name' => 'website-settings.view'])->id,
        Permission::factory()->create(['name' => 'website-settings.update'])->id,
    ]);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);
}

function useStagyTheme(): void
{
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();
}

test('per-language settings are saved as one value per locale', function () {
    actingAsSettingsEditor();

    $this->putJson('/api/v1/admin/website-settings/contact', [
        'email' => 'halo@stagy.id',
        'whatsapp' => '0812 3456 7890',
        'business_hours' => ['id' => 'Senin–Jumat, 09.00–17.00 WIB', 'en' => 'Mon–Fri, 9am–5pm WIB'],
    ])->assertSuccessful()
        ->assertJsonPath('data.settings.whatsapp', '0812 3456 7890')
        ->assertJsonPath('data.settings.business_hours.en', 'Mon–Fri, 9am–5pm WIB');

    $this->putJson('/api/v1/admin/website-settings/social', ['tiktok_url' => 'https://www.tiktok.com/@stagy'])
        ->assertSuccessful()
        ->assertJsonPath('data.settings.tiktok_url', 'https://www.tiktok.com/@stagy');
});

test('per-language settings reject unknown languages and overlong text', function (array $tagline) {
    actingAsSettingsEditor();

    $this->putJson('/api/v1/admin/website-settings/general', [
        'site_name' => 'Stagy', 'tagline' => $tagline, 'locale' => 'id_ID', 'timezone' => 'Asia/Jakarta',
    ])->assertUnprocessable()->assertJsonValidationErrors('tagline');
})->with([
    'unsupported language' => [['fr' => 'Bonjour']],
    'too long' => [['id' => str_repeat('a', 501)]],
    'nested value' => [['id' => ['not text']]],
]);

test('the whatsapp number must look like a phone number', function () {
    actingAsSettingsEditor();

    $this->putJson('/api/v1/admin/website-settings/contact', ['whatsapp' => 'chat me'])
        ->assertUnprocessable()->assertJsonValidationErrors('whatsapp');
});

test('the public site shows each language its own text and falls back to the default language', function () {
    useStagyTheme();
    WebsiteSetting::factory()->create(['group' => 'general', 'settings' => ['site_name' => 'Stagy', 'tagline' => ['id' => 'Tagline Indonesia', 'en' => 'English tagline']]]);
    WebsiteSetting::factory()->create(['group' => 'contact', 'settings' => ['business_hours' => ['id' => 'Senin–Jumat', 'en' => null]]]);
    Page::factory()->published()->create(['slug' => 'tentang', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'about', 'locale' => 'en']);

    $this->get('/tentang')->assertSuccessful()->assertSee('Tagline Indonesia')->assertSee('Senin–Jumat');
    $this->get('/en/about')->assertSuccessful()->assertSee('English tagline')->assertDontSee('Tagline Indonesia')->assertSee('Senin–Jumat');
});

test('a legacy plain-text setting still serves every language', function () {
    useStagyTheme();
    WebsiteSetting::factory()->create(['group' => 'general', 'settings' => ['site_name' => 'Stagy', 'tagline' => 'Satu tagline']]);
    Page::factory()->published()->create(['slug' => 'about', 'locale' => 'en']);

    $this->get('/en/about')->assertSuccessful()->assertSee('Satu tagline');
});

test('the public settings API resolves per-language values for the requested locale', function () {
    WebsiteSetting::factory()->create(['group' => 'seo', 'settings' => ['default_title' => ['id' => 'Judul', 'en' => 'Title'], 'social_image_id' => null]]);

    $this->getJson('/api/v1/public/website-settings?locale=en')
        ->assertSuccessful()
        ->assertJsonPath('data.0.settings.default_title', 'Title');
});

test('the stagy theme uses the uploaded logo and favicon', function () {
    useStagyTheme();
    $logo = Media::factory()->create();
    $favicon = Media::factory()->create();
    WebsiteSetting::factory()->create(['group' => 'branding', 'settings' => ['logo_id' => $logo->id, 'favicon_id' => $favicon->id]]);
    Page::factory()->published()->create(['slug' => 'tentang', 'locale' => 'id']);

    $this->get('/tentang')
        ->assertSuccessful()
        ->assertSee('<img src="'.$logo->url.'"', false)
        ->assertSee('<link rel="icon" href="'.$favicon->url.'">', false)
        ->assertDontSee('favicon-32', false);
});
