<?php

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Themes\Services\ThemeManager;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use App\Models\WebsiteSetting;
use App\Rules\ValidContentBlocks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(LazilyRefreshDatabase::class);

function seedStagySite(): void
{
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    test()->seed(DatabaseSeeder::class);
}

test('the stagy seed creates the mockup content in Indonesian', function () {
    seedStagySite();

    expect(Page::query()->where('locale', 'id')->pluck('slug')->sort()->values()->all())
        ->toBe(['blog', 'cara-kerja', 'demo', 'faq', 'fitur', 'harga', 'home', 'kebijakan-privasi', 'syarat-ketentuan'])
        ->and(Menu::query()->where('locale', 'id')->pluck('location')->sort()->values()->all())->toBe(['footer-help', 'footer-product', 'header'])
        ->and(PricingPlan::query()->where('locale', 'id')->orderBy('position')->pluck('name')->all())->toBe(['Basic', 'Professional', 'Enterprise'])
        ->and(PricingComparisonRow::query()->where('locale', 'id')->count())->toBe(21)
        ->and(PricingAddon::query()->where('locale', 'id')->count())->toBe(11)
        ->and(Category::query()->count())->toBe(4)
        ->and(Post::query()->count())->toBe(7)
        ->and(Post::query()->where('is_featured', true)->value('slug'))->toBe('cara-membuat-buku-tamu-digital-untuk-event-korporat')
        ->and(WebsiteSetting::query()->where('group', 'general')->value('settings')['site_name'])->toBe('Stagy');

    expect(Page::query()->whereNull('translation_group_id')->exists())->toBeFalse()
        ->and(PricingPlan::query()->whereNull('translation_group_id')->exists())->toBeFalse();
});

test('every seeded page passes the content block schemas', function () {
    seedStagySite();

    Page::query()->each(function (Page $page) {
        $validator = Validator::make(['blocks' => $page->blocks], ['blocks' => [new ValidContentBlocks(app(ContentBlockRegistry::class))]]);

        expect($validator->errors()->all())->toBe([], "Page [{$page->slug}] has invalid blocks.");
    });
});

test('the stagy seed switches off services and portfolio but keeps the contact API for the demo form', function () {
    seedStagySite();

    expect(FeatureFlag::query()->where('name', 'services')->value('enabled'))->toBeFalse()
        ->and(FeatureFlag::query()->where('name', 'portfolio')->value('enabled'))->toBeFalse()
        ->and(FeatureFlag::query()->where('name', 'contact-messages')->value('enabled'))->toBeTrue()
        ->and(FeatureFlag::query()->where('name', 'pricing')->value('enabled'))->toBeTrue();
});

test('running the seed again changes nothing and keeps editors\' changes', function () {
    seedStagySite();
    Page::query()->where('slug', 'harga')->update(['title' => 'Harga (diedit)']);
    WebsiteSetting::query()->where('group', 'general')->first()->update(['settings' => ['site_name' => 'Stagy ID', 'tagline' => 'Tagline sendiri']]);

    test()->seed(DatabaseSeeder::class);

    expect(Page::query()->count())->toBe(17)
        ->and(Page::query()->where('slug', 'harga')->value('title'))->toBe('Harga (diedit)')
        ->and(PricingPlan::query()->count())->toBe(6)
        ->and(PricingComparisonRow::query()->count())->toBe(42)
        ->and(Post::query()->count())->toBe(7)
        ->and(Menu::query()->count())->toBe(6)
        ->and(WebsiteSetting::query()->where('group', 'general')->value('settings'))->toMatchArray(['site_name' => 'Stagy ID', 'tagline' => 'Tagline sendiri']);
});

test('every seeded page renders', function (string $path, string $expected) {
    seedStagySite();

    $this->get($path)->assertSuccessful()->assertSee($expected, false);
})->with([
    'home' => ['/', 'Registrasi &amp; check-in event klien Anda'],
    'features' => ['/fitur', 'Tamu terdata, undangan terkirim'],
    'how it works' => ['/cara-kerja', 'Tim on-site · 3 alat tap ID card'],
    'pricing' => ['/harga', 'Rp11,6 juta'],
    'faq' => ['/faq', 'Bisakah check-in memakai HP?'],
    'demo' => ['/demo', 'data-demo-form'],
    'terms' => ['/syarat-ketentuan', 'Pemesanan &amp; pembayaran'],
    'privacy' => ['/kebijakan-privasi', 'Data yang kami kumpulkan'],
    'blog' => ['/blog', 'Cara membuat buku tamu digital untuk event korporat'],
    'article' => ['/blog/cara-membuat-buku-tamu-digital-untuk-event-korporat', 'Rapikan daftar tamu dari klien'],
]);

test('the header and footer menus link to the seeded pages', function () {
    seedStagySite();

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('href="/cara-kerja"', false)
        ->assertSee('href="/blog"', false)
        ->assertSee('href="/kebijakan-privasi"', false);
});

test('the default theme seed gives its content translation groups', function () {
    test()->seed(DatabaseSeeder::class);

    expect(Page::query()->count())->toBeGreaterThan(0)
        ->and(Page::query()->whereNull('translation_group_id')->exists())->toBeFalse()
        ->and(Menu::query()->whereNull('translation_group_id')->exists())->toBeFalse()
        ->and(Post::query()->whereNull('translation_group_id')->exists())->toBeFalse();
});
