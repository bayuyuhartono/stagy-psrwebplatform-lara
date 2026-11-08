<?php

use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Page;
use Database\Seeders\StagySiteSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    foreach (['pages', 'posts', 'menus', 'seo'] as $feature) {
        FeatureFlag::factory()->create(['name' => $feature, 'enabled' => true]);
    }

    $this->seed(StagySiteSeeder::class);
});

test('every Indonesian page has an English translation the language switcher links to', function () {
    $pricing = Page::query()->where('slug', 'harga')->where('locale', 'id')->first();

    expect(Page::query()->where('locale', 'en')->where('translation_group_id', $pricing->translation_group_id)->value('slug'))->toBe('pricing');

    $this->get('/harga')->assertSee('href="'.url('/en/pricing').'" hreflang="en"', escape: false);
});

test('the English site has its own content, menus and pricing', function () {
    $this->get('/en')
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="index,follow">', escape: false)
        ->assertSee('How It Works')
        ->assertSee('IDR 8.1 million')
        ->assertDontSee('Minta Demo');

    $this->get('/en/pricing')
        ->assertSuccessful()
        ->assertSee('Talk to us about Enterprise')
        ->assertSee('IDR 17,500')
        ->assertDontSee('Rp17.500');
});
