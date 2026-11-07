<?php

use App\Features\Menus\Enums\MenuItemType;
use App\Features\Themes\Services\ThemeManager;
use App\Features\WebsiteSettings\Services\WhatsAppLink;
use App\Models\FeatureFlag;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function stagyMenu(string $location, string $locale, array $pages): Menu
{
    $menu = Menu::factory()->create(['location' => $location, 'locale' => $locale, 'name' => "{$location} {$locale}"]);

    foreach ($pages as $position => $page) {
        MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'type' => MenuItemType::Page,
            'page_id' => $page->id,
            'url' => null,
            'label' => $page->title,
            'position' => $position,
        ]);
    }

    return $menu;
}

test('the stagy theme declares its menu locations and settings', function () {
    $manifest = app(ThemeManager::class)->active();

    expect($manifest->slug)->toBe('stagy')
        ->and($manifest->menuLocations)->toHaveKeys(['header', 'footer-product', 'footer-help'])
        ->and($manifest->defaults())->toMatchArray(['demo_page_slug' => 'demo', 'show_whatsapp_button' => true]);
});

test('pages render inside the stagy layout in the current language', function () {
    Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en']);

    $this->get('/harga')
        ->assertSuccessful()
        ->assertSee('<html lang="id">', false)
        ->assertSee('stagy', false)
        ->assertSee('Minta Demo')
        ->assertSee('href="'.url('/demo').'"', false)
        ->assertSee('Langsung ke konten');

    $this->get('/en/pricing')
        ->assertSuccessful()
        ->assertSee('<html lang="en">', false)
        ->assertSee('Request a Demo')
        ->assertSee('href="'.url('/en/demo').'"', false);
});

test('the header renders the current language menu and marks the current page', function () {
    $pricing = Page::factory()->published()->create(['slug' => 'harga', 'title' => 'Harga', 'locale' => 'id']);
    $faq = Page::factory()->published()->create(['slug' => 'faq', 'title' => 'Tanya Jawab', 'locale' => 'id']);
    $englishPricing = Page::factory()->published()->create(['slug' => 'pricing', 'title' => 'Pricing', 'locale' => 'en']);
    stagyMenu('header', 'id', [$pricing, $faq]);
    stagyMenu('header', 'en', [$englishPricing]);

    $response = $this->get('/harga')->assertSuccessful();

    expect($response->getContent())
        ->toMatch('#href="/harga" target="_self"\s+aria-current="page"#')
        ->not->toMatch('#href="/faq" target="_self"\s+aria-current="page"#')
        ->toContain('Tanya Jawab')
        ->not->toContain('>Pricing<');

    $this->get('/en/pricing')->assertSuccessful()->assertSee('href="/en/pricing"', false)->assertDontSee('Tanya Jawab');
});

test('the footer shows footer menus, contact details and social links from settings', function () {
    $faq = Page::factory()->published()->create(['slug' => 'faq', 'title' => 'FAQ', 'locale' => 'id']);
    stagyMenu('footer-help', 'id', [$faq]);
    WebsiteSetting::factory()->create(['group' => 'contact', 'settings' => ['email' => 'halo@stagy.id', 'whatsapp' => '0812-3456-7890', 'address' => 'Jl. Contoh 1', 'business_hours' => 'Senin–Jumat, 09.00–17.00 WIB']]);
    WebsiteSetting::factory()->create(['group' => 'social', 'settings' => ['instagram_url' => 'https://instagram.com/stagy']]);

    $this->get('/faq')
        ->assertSuccessful()
        ->assertSee('Bantuan')
        ->assertSee('mailto:halo@stagy.id', false)
        ->assertSee('https://wa.me/6281234567890', false)
        ->assertSee('Senin–Jumat, 09.00–17.00 WIB')
        ->assertSee('aria-label="Instagram Stagy"', false)
        ->assertDontSee('aria-label="YouTube Stagy"', false)
        ->assertSee('Powered by');
});

test('the floating WhatsApp button only appears when a number is configured', function () {
    Page::factory()->published()->create(['slug' => 'faq', 'locale' => 'id']);

    $this->get('/faq')->assertSuccessful()->assertDontSee('aria-label="Chat WhatsApp"', false);

    WebsiteSetting::factory()->create(['group' => 'contact', 'settings' => ['whatsapp' => '+62 812 0000 1111']]);

    $this->get('/faq')->assertSuccessful()->assertSee('aria-label="Chat WhatsApp"', false);
});

test('the language switcher links to the translation or falls back to the other home page', function () {
    $harga = Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en', 'translation_group_id' => $harga->translation_group_id]);
    Page::factory()->published()->create(['slug' => 'tentang', 'locale' => 'id']);

    $this->get('/harga')->assertSuccessful()->assertSee('href="'.url('/en/pricing').'" hreflang="en"', false);
    $this->get('/tentang')->assertSuccessful()->assertSee('href="'.url('/en').'" hreflang="en"', false);
});

test('missing pages render the stagy not-found page in the current language', function () {
    $this->get('/tidak-ada')->assertNotFound()->assertSee('Tiket tidak ditemukan')->assertSee('daftar');
    $this->get('/en/missing')->assertNotFound()->assertSee('Ticket not found')->assertSee('Go to Home');
});

test('whatsapp links normalise local Indonesian numbers', function (?string $number, ?string $expected) {
    expect(app(WhatsAppLink::class)->url($number))->toBe($expected);
})->with([
    'local format' => ['0812-3456-7890', 'https://wa.me/6281234567890'],
    'international format' => ['+62 812 3456 7890', 'https://wa.me/6281234567890'],
    'empty' => ['', null],
    'missing' => [null, null],
]);
