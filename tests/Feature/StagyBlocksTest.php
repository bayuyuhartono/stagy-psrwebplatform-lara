<?php

use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Rules\ContentLink;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function createPageAs(string $permission, array $blocks)
{
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => $permission]));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return test()->postJson('/api/v1/admin/pages', [
        'title' => 'Beranda', 'slug' => 'beranda-'.fake()->unique()->numberBetween(), 'status' => 'draft', 'blocks' => $blocks,
    ]);
}

function stagyPage(array $blocks): Page
{
    return Page::factory()->published()->create(['slug' => 'contoh', 'locale' => 'id', 'blocks' => $blocks]);
}

/** @return array<string, array<string, mixed>> */
function validStagyBlocks(): array
{
    return [
        'hero' => ['type' => 'hero', 'data' => [
            'badge' => 'Registrasi & check-in untuk Event Organizer',
            'heading' => 'Registrasi event klien Anda, *beres* dalam satu sistem.',
            'subheading' => 'Import tamu, kirim undangan QR.',
            'primary_action' => ['label' => 'Minta Demo', 'url' => '/demo'],
            'secondary_action' => ['label' => 'Lihat Paket', 'url' => '/harga'],
            'trust_points' => ['Data tamu terlindungi', 'Tim on-site saat hari-H'],
            'visual' => 'ticket',
            'ticket_event' => 'Annual Gathering 2026',
            'ticket_guest' => 'Budi Santoso',
        ]],
        'page_header' => ['type' => 'page_header', 'data' => [
            'eyebrow' => 'Fitur', 'heading' => 'Semua yang dibutuhkan EO', 'links' => [['label' => '01 Sebelum event', 'url' => '#sebelum']],
        ]],
        'feature_cards' => ['type' => 'feature_cards', 'data' => [
            'eyebrow' => 'Fitur', 'heading' => 'Dari undangan sampai laporan', 'link' => ['label' => 'Lihat semua fitur', 'url' => '/fitur'],
            'items' => [['title' => 'Sebelum event', 'description' => 'Import Excel.', 'url' => '/fitur#sebelum'], ['title' => 'Di venue']],
        ]],
        'steps' => ['type' => 'steps', 'data' => [
            'heading' => 'Empat langkah', 'variant' => 'detailed',
            'items' => [['title' => 'Kirim data tamu', 'you' => 'Kirim Excel.', 'us' => 'Rapikan data.'], ['title' => 'Laporan siap', 'you' => 'Teruskan ke klien.']],
        ]],
        'feature_group' => ['type' => 'feature_group', 'data' => [
            'anchor' => 'sebelum', 'label' => 'Sebelum event', 'heading' => 'Tamu terdata', 'points' => ['Import data tamu dari Excel'], 'media_placeholder' => 'Screenshot: daftar tamu',
        ]],
        'faq' => ['type' => 'faq', 'data' => [
            'heading' => 'Pertanyaan yang sering *ditanyakan*', 'show_whatsapp' => true,
            'items' => [['question' => 'Bisakah check-in memakai HP?', 'answer' => 'Bisa.'], ['question' => 'Perlu internet?', 'answer' => 'Ya.']],
        ]],
        'legal_sections' => ['type' => 'legal_sections', 'data' => [
            'eyebrow' => 'Legal', 'heading' => 'Syarat & Ketentuan', 'updated' => 'Terakhir diperbarui: 1 Nov 2026',
            'sections' => [['title' => 'Definisi', 'body' => 'Isi definisi.'], ['title' => 'Pembayaran', 'body' => 'Isi pembayaran.']],
        ]],
        'call_to_action' => ['type' => 'call_to_action', 'data' => [
            'heading' => 'Siap membuat event lebih rapi?', 'label' => 'Minta Demo', 'url' => '/demo',
            'secondary_action' => ['label' => 'Chat WhatsApp', 'url' => 'whatsapp'],
        ]],
    ];
}

test('the new block types validate against their schemas', function () {
    createPageAs('pages.create', array_values(validStagyBlocks()))->assertSuccessful();
});

test('block schemas reject invalid payloads', function (array $block, string $message) {
    createPageAs('pages.create', [$block])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('blocks');
})->with([
    'feature cards need items' => [['type' => 'feature_cards', 'data' => ['heading' => 'Fitur', 'items' => []]], 'items'],
    'feature group needs points' => [['type' => 'feature_group', 'data' => ['heading' => 'Tamu terdata']], 'points'],
    'anchor must be a plain id' => [['type' => 'feature_group', 'data' => ['heading' => 'X', 'points' => ['a'], 'anchor' => 'not valid!']], 'anchor'],
    'steps variant is constrained' => [['type' => 'steps', 'data' => ['variant' => 'carousel', 'items' => [['title' => 'A']]]], 'variant'],
    'unknown keys are rejected' => [['type' => 'faq', 'data' => ['items' => [['question' => 'Q', 'answer' => 'A']], 'colour' => 'red']], 'colour'],
    'script links are rejected' => [['type' => 'call_to_action', 'data' => ['heading' => 'Hi', 'label' => 'Go', 'url' => 'javascript:alert(1)']], 'url'],
]);

test('content links accept paths, anchors, contact links and the whatsapp keyword', function (string $link, bool $valid) {
    expect(ContentLink::isValid($link))->toBe($valid);
})->with([
    ['/harga', true],
    ['#venue', true],
    ['https://stagy.id/demo', true],
    ['mailto:halo@stagy.id', true],
    ['tel:+62 812 3456', true],
    ['whatsapp', true],
    ['//evil.example', false],
    ['javascript:alert(1)', false],
    ['ftp://files.example', false],
    ['harga', false],
]);

test('the block schema endpoint marks schema-driven blocks for the generated CMS form', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => 'pages.create']));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/admin/content-blocks/schema')
        ->assertSuccessful()
        ->assertJsonPath('data.feature_group.editor', 'schema')
        ->assertJsonPath('data.feature_group.fields.points.type', 'list')
        ->assertJsonPath('data.faq.fields.items.fields.answer.type', 'text')
        ->assertJsonPath('data.rich_text.editor', 'custom');
});

test('every block type renders in the stagy theme', function () {
    stagyPage(array_values(validStagyBlocks()));

    $this->get('/contoh')
        ->assertSuccessful()
        ->assertSee('Registrasi &amp; check-in untuk Event Organizer', false)
        ->assertSee('<span class="text-public-accent">beres</span>', false)
        ->assertSee('Annual Gathering 2026')
        ->assertSee('href="#sebelum"', false)
        ->assertSee('Lihat semua fitur')
        ->assertSee('Rapikan data.')
        ->assertSee('id="sebelum"', false)
        ->assertSee('Ganti dengan screenshot asli')
        ->assertSee('Bisakah check-in memakai HP?')
        ->assertSee('Daftar isi')
        ->assertSee('href="#bagian-2"', false)
        ->assertSee('Siap membuat event lebih rapi?');
});

test('highlight markup is escaped before the highlight is applied', function () {
    stagyPage([['type' => 'page_header', 'data' => ['heading' => '<script>alert(1)</script> *aman*']]]);

    $this->get('/contoh')
        ->assertSuccessful()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; <span class="text-public-accent">aman</span>', false);
});

test('whatsapp buttons use the configured number and are hidden without one', function () {
    $blocks = [validStagyBlocks()['call_to_action']];
    stagyPage($blocks);

    $this->get('/contoh')->assertSuccessful()->assertSee('Minta Demo')->assertDontSee('>Chat WhatsApp<', false);

    WebsiteSetting::factory()->create(['group' => 'contact', 'settings' => ['whatsapp' => '0812 1111 2222']]);

    $this->get('/contoh')->assertSuccessful()->assertSee('href="https://wa.me/6281211112222"', false);
});

test('consecutive feature groups alternate their layout and are numbered in order', function () {
    $group = fn (string $heading) => ['type' => 'feature_group', 'data' => ['label' => $heading, 'heading' => $heading, 'points' => ['x']]];
    stagyPage([$group('Sebelum'), $group('Venue')]);

    $content = $this->get('/contoh')->assertSuccessful()->getContent();

    expect($content)
        ->toContain('flex-row bg-public-mist')
        ->toContain('flex-row-reverse bg-public-paper')
        ->toContain('01 · Sebelum')
        ->toContain('02 · Venue');
});

test('legacy hero and call to action payloads still validate and render', function () {
    createPageAs('pages.create', [
        ['type' => 'hero', 'data' => ['heading' => 'Old hero', 'primary_action' => ['label' => 'Go', 'url' => 'https://example.com']]],
        ['type' => 'call_to_action', 'data' => ['heading' => 'Old CTA', 'label' => 'Contact', 'url' => 'https://example.com/contact']],
    ])->assertSuccessful();
});
