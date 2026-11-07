<?php

use App\Features\Pricing\Services\PriceFormatter;
use App\Features\Themes\Services\ThemeManager;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pricing', 'enabled' => true]);
});

function actingAsPricingManager(string ...$permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name) => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array{basic: PricingPlan, pro: PricingPlan, enterprise: PricingPlan} */
function seedIndonesianPlans(): array
{
    return [
        'basic' => PricingPlan::factory()->create(['name' => 'Basic', 'price' => 4_000_000, 'position' => 1, 'locale' => 'id', 'support_summary' => 'Tim on-site · 2 QR scanner']),
        'pro' => PricingPlan::factory()->create(['name' => 'Professional', 'price' => 8_100_000, 'position' => 2, 'locale' => 'id', 'is_highlighted' => true, 'highlight_label' => 'Paling Populer']),
        'enterprise' => PricingPlan::factory()->create(['name' => 'Enterprise', 'price' => 11_600_000, 'position' => 3, 'locale' => 'id']),
    ];
}

test('prices are formatted the way the pricing page reads them', function (int $amount, string $locale, string $expected) {
    expect((new PriceFormatter)->format($amount, $locale))->toBe($expected);
})->with([
    [4_000_000, 'id', 'Rp4 juta'],
    [8_100_000, 'id', 'Rp8,1 juta'],
    [11_600_000, 'id', 'Rp11,6 juta'],
    [2_500, 'id', 'Rp2.500'],
    [700_000, 'id', 'Rp700.000'],
    [8_100_000, 'en', 'IDR 8.1 million'],
    [27_500, 'en', 'IDR 27,500'],
]);

test('a pricing manager can create, update, delete and restore a plan', function () {
    actingAsPricingManager('pricing.create', 'pricing.update', 'pricing.delete', 'pricing.restore');

    $id = $this->postJson('/api/v1/admin/pricing/plans', [
        'name' => 'Basic', 'price' => 4_000_000, 'extra_guest_price' => 2_500, 'features' => ['Tim on-site'], 'cta_url' => '/demo',
    ])->assertSuccessful()
        ->assertJsonPath('data.locale', 'id')
        ->assertJsonPath('data.price_formatted', 'Rp4 juta')
        ->assertJsonPath('data.extra_guest_price_formatted', 'Rp2.500')
        ->json('data.id');

    $this->putJson("/api/v1/admin/pricing/plans/{$id}", ['price' => 4_500_000])->assertSuccessful()->assertJsonPath('data.price_formatted', 'Rp4,5 juta');
    $this->deleteJson("/api/v1/admin/pricing/plans/{$id}")->assertNoContent();
    $this->postJson("/api/v1/admin/pricing/plans/{$id}/restore")->assertSuccessful();

    $this->assertDatabaseHas('audit_logs', ['event' => 'pricing-plan.created']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'pricing-plan.restored']);
});

test('the admin pricing lists are scoped to one language and ordered', function () {
    actingAsPricingManager('pricing.view');
    PricingPlan::factory()->create(['name' => 'Second', 'position' => 2, 'locale' => 'id']);
    PricingPlan::factory()->create(['name' => 'First', 'position' => 1, 'locale' => 'id']);
    PricingPlan::factory()->create(['name' => 'English', 'locale' => 'en']);

    $this->getJson('/api/v1/admin/pricing/plans')->assertSuccessful()->assertJsonCount(2, 'data')->assertJsonPath('data.0.name', 'First');
    $this->getJson('/api/v1/admin/pricing/plans?locale=en')->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'English');
});

test('pricing writes require the pricing permissions', function () {
    actingAsPricingManager('pricing.view');

    $this->postJson('/api/v1/admin/pricing/addons', ['name' => 'QR scanner', 'price' => 100_000])->assertForbidden();
});

test('the pricing module is hidden when its feature flag is off', function () {
    FeatureFlag::query()->where('name', 'pricing')->update(['enabled' => false]);
    actingAsPricingManager('pricing.view');

    $this->getJson('/api/v1/admin/pricing/plans')->assertNotFound();
    $this->getJson('/api/v1/public/pricing')->assertNotFound();
});

test('comparison values must reference plans in the same language', function () {
    actingAsPricingManager('pricing.create');
    ['basic' => $basic] = seedIndonesianPlans();
    $english = PricingPlan::factory()->create(['locale' => 'en']);

    $payload = ['group_name' => 'Check-in', 'label' => 'QR scanner'];

    $this->postJson('/api/v1/admin/pricing/comparison-rows', [...$payload, 'values' => [$basic->id => '2']])->assertSuccessful();
    $this->postJson('/api/v1/admin/pricing/comparison-rows', [...$payload, 'values' => [$english->id => true]])
        ->assertUnprocessable()->assertJsonValidationErrors('values');
    $this->postJson('/api/v1/admin/pricing/comparison-rows', [...$payload, 'values' => [$basic->id => ['nested']]])
        ->assertUnprocessable()->assertJsonValidationErrors('values');
});

test('plan links must be safe content links', function () {
    actingAsPricingManager('pricing.create');

    $this->postJson('/api/v1/admin/pricing/plans', ['name' => 'Basic', 'price' => 1, 'features' => [], 'cta_url' => 'javascript:alert(1)'])
        ->assertUnprocessable()->assertJsonValidationErrors('cta_url');
});

test('the public catalog lifts rows every plan shares out of the comparison table', function () {
    ['basic' => $basic, 'pro' => $pro, 'enterprise' => $enterprise] = seedIndonesianPlans();
    $all = fn ($value) => [$basic->id => $value, $pro->id => $value, $enterprise->id => $value];
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Laporan', 'label' => 'Export Excel', 'values' => $all(true), 'position' => 1]);
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Registrasi', 'label' => 'Undangan WhatsApp', 'values' => $all('200 tamu'), 'position' => 2]);
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Venue', 'label' => 'Gate otomatis', 'values' => [$enterprise->id => true], 'position' => 3]);
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Venue', 'label' => 'Nothing yet', 'values' => [], 'position' => 4]);
    PricingAddon::factory()->create(['locale' => 'id', 'name' => 'QR scanner tambahan', 'price' => 100_000, 'unit' => '/ unit']);

    $this->getJson('/api/v1/public/pricing')
        ->assertSuccessful()
        ->assertJsonPath('data.plans.0.name', 'Basic')
        ->assertJsonPath('data.plans.1.is_highlighted', true)
        ->assertJsonPath('data.included_in_all', ['Export Excel', 'Undangan WhatsApp: 200 tamu'])
        ->assertJsonCount(1, 'data.comparison')
        ->assertJsonPath('data.comparison.0.title', 'Venue')
        ->assertJsonPath('data.comparison.0.rows.0.cells', [false, false, true])
        ->assertJsonPath('data.addons.0.price_formatted', 'Rp100.000');
});

test('the public catalog follows the requested language', function () {
    PricingPlan::factory()->create(['name' => 'Paket ID', 'locale' => 'id']);
    PricingPlan::factory()->create(['name' => 'English plan', 'locale' => 'en', 'price' => 4_000_000]);

    $this->getJson('/api/v1/public/pricing?locale=en')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.plans')
        ->assertJsonPath('data.plans.0.name', 'English plan')
        ->assertJsonPath('data.plans.0.price_formatted', 'IDR 4 million');
});

test('pricing blocks render plans, the comparison table, add-ons and on-site support', function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);

    ['basic' => $basic, 'pro' => $pro, 'enterprise' => $enterprise] = seedIndonesianPlans();
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Check-in di venue', 'label' => 'Alat tap kartu', 'values' => [$basic->id => false, $pro->id => '3', $enterprise->id => false]]);
    PricingComparisonRow::factory()->create(['locale' => 'id', 'group_name' => 'Laporan', 'label' => 'Export Excel', 'values' => [$basic->id => true, $pro->id => true, $enterprise->id => true]]);
    PricingAddon::factory()->create(['locale' => 'id', 'name' => 'Laptop titik check-in', 'price' => 150_000, 'unit' => '/ unit']);

    Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id', 'blocks' => [
        ['type' => 'pricing_cards', 'data' => ['variant' => 'full', 'note' => 'Harga per event.']],
        ['type' => 'pricing_cards', 'data' => ['variant' => 'summary', 'heading' => 'Harga jelas per event.']],
        ['type' => 'pricing_table', 'data' => ['heading' => 'Bandingkan isi tiap paket.']],
        ['type' => 'pricing_addons', 'data' => ['heading' => 'Tambah sesuai kebutuhan klien.']],
        ['type' => 'support_tiers', 'data' => ['heading' => 'Pendampingan di venue, sesuai paket.']],
    ]]);

    $this->get('/harga')
        ->assertSuccessful()
        ->assertSee('Rp8,1 juta')
        ->assertSee('Paling Populer')
        ->assertSee('+Rp2.500 per tamu tambahan')
        ->assertSee('Harga per event.')
        ->assertSee('Alat tap kartu')
        ->assertSee('Tidak termasuk')
        ->assertSee('1 fitur ini ada di Basic, Professional, dan Enterprise.')
        ->assertSee('Laptop titik check-in')
        ->assertSee('Rp150.000')
        ->assertSee('Tim on-site · 2 QR scanner');
});

test('the CMS pricing page is available to pricing viewers', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => 'pricing.view']));
    $user = User::factory()->create();
    $user->roles()->attach($role);

    $this->actingAs($user)->get('/cms/pricing')->assertSuccessful()->assertSee('data-pricing-manager', false);
    $this->actingAs(User::factory()->create())->get('/cms/pricing')->assertForbidden();
});
