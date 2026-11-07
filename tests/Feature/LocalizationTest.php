<?php

use App\Features\Localization\Services\AlternateUrlResolver;
use App\Features\Posts\Enums\PostType;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(['pages', 'posts', 'menus', 'seo'])->each(fn (string $name) => FeatureFlag::factory()->create(['name' => $name, 'enabled' => true]));
});

function actingAsAdministratorWith(string ...$permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name) => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('new content defaults to the default locale in its own translation group', function () {
    $page = Page::factory()->create();

    expect($page->locale)->toBe('id')
        ->and($page->translation_group_id)->toBeUuid();
});

test('the default locale is served at the root and other locales under their prefix', function () {
    Page::factory()->published()->create(['slug' => 'harga', 'title' => 'Harga Paket', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'harga', 'title' => 'Package Pricing', 'locale' => 'en']);

    $this->get('/harga')->assertSuccessful()->assertSee('Harga Paket')->assertDontSee('Package Pricing')->assertSee('<html lang="id">', false);
    $this->get('/en/harga')->assertSuccessful()->assertSee('Package Pricing')->assertDontSee('Harga Paket')->assertSee('<html lang="en">', false);
});

test('a page is not reachable in a locale it was not written in', function () {
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en']);
    Page::factory()->published()->create(['slug' => 'tentang', 'locale' => 'id']);

    $this->get('/pricing')->assertNotFound();
    $this->get('/en/tentang')->assertNotFound();
});

test('translated pages link to each other with hreflang alternates', function () {
    $indonesian = Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en', 'translation_group_id' => $indonesian->translation_group_id]);

    $this->get('/harga')
        ->assertSuccessful()
        ->assertSee('<link rel="alternate" hreflang="id" href="'.url('/harga').'">', false)
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/pricing').'">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/harga').'">', false);
});

test('unpublished translations are not advertised as alternates', function () {
    $indonesian = Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->create(['slug' => 'pricing', 'locale' => 'en', 'translation_group_id' => $indonesian->translation_group_id]);

    $this->get('/harga')->assertSuccessful()->assertDontSee('hreflang="en"', false);
});

test('the blog lists only posts written in the requested locale', function () {
    Post::factory()->published()->create(['title' => 'Tips Registrasi', 'type' => PostType::Blog, 'locale' => 'id']);
    Post::factory()->published()->create(['title' => 'Registration Tips', 'type' => PostType::Blog, 'locale' => 'en']);

    $this->get('/blog')->assertSuccessful()->assertSee('Tips Registrasi')->assertDontSee('Registration Tips');
    $this->get('/en/blog')->assertSuccessful()->assertSee('Registration Tips')->assertDontSee('Tips Registrasi');
});

test('category archives resolve the category in the current locale', function () {
    $category = Category::factory()->create(['slug' => 'panduan', 'locale' => 'id']);
    Post::factory()->published()->create(['type' => PostType::Blog, 'locale' => 'id'])->categories()->attach($category);

    $this->get('/blog/category/panduan')->assertSuccessful();
    $this->get('/en/blog/category/panduan')->assertNotFound();
});

test('the public API serves content in the requested locale', function () {
    Page::factory()->published()->create(['slug' => 'harga', 'title' => 'Harga Paket', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'harga', 'title' => 'Package Pricing', 'locale' => 'en']);

    $this->getJson('/api/v1/public/pages/harga')->assertSuccessful()->assertJsonPath('data.title', 'Harga Paket');
    $this->getJson('/api/v1/public/pages/harga?locale=en')
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Package Pricing')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.canonical_url', url('/en/harga'));
});

test('the public API rejects an unsupported locale', function () {
    $this->getJson('/api/v1/public/pages/harga?locale=fr')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale');
});

test('the public API exposes published translations of a page', function () {
    $indonesian = Page::factory()->published()->create(['slug' => 'harga', 'locale' => 'id']);
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en', 'translation_group_id' => $indonesian->translation_group_id]);

    $this->getJson('/api/v1/public/pages/harga')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.translations')
        ->assertJsonFragment(['locale' => 'en', 'slug' => 'pricing', 'url' => url('/en/pricing')]);
});

test('menus are resolved per locale', function () {
    Menu::factory()->create(['location' => 'header', 'name' => 'Menu Utama', 'locale' => 'id']);
    Menu::factory()->create(['location' => 'header', 'name' => 'Main Menu', 'locale' => 'en']);

    $this->getJson('/api/v1/public/menus/header')->assertSuccessful()->assertJsonPath('data.name', 'Menu Utama');
    $this->getJson('/api/v1/public/menus/header?locale=en')->assertSuccessful()->assertJsonPath('data.name', 'Main Menu');
});

test('an administrator can create a translation of an existing page', function () {
    actingAsAdministratorWith('pages.create');
    $source = Page::factory()->create(['slug' => 'harga', 'locale' => 'id']);

    $response = $this->postJson('/api/v1/admin/pages', [
        'title' => 'Pricing',
        'slug' => 'pricing',
        'status' => 'draft',
        'blocks' => [],
        'locale' => 'en',
        'translation_of' => $source->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.translation_group_id', $source->translation_group_id);
});

test('a translation group cannot hold two rows in the same locale', function () {
    actingAsAdministratorWith('pages.create');
    $source = Page::factory()->create(['locale' => 'id']);
    Page::factory()->create(['locale' => 'en', 'translation_group_id' => $source->translation_group_id]);

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Another English Copy', 'slug' => 'another-copy', 'status' => 'draft', 'blocks' => [],
        'locale' => 'en', 'translation_of' => $source->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('translation_of');
});

test('slugs are unique within a locale but may repeat across locales', function () {
    actingAsAdministratorWith('pages.create');
    Page::factory()->create(['slug' => 'faq', 'locale' => 'id']);

    $payload = ['title' => 'FAQ', 'slug' => 'faq', 'status' => 'draft', 'blocks' => []];

    $this->postJson('/api/v1/admin/pages', [...$payload, 'locale' => 'id'])->assertUnprocessable()->assertJsonValidationErrors('slug');
    $this->postJson('/api/v1/admin/pages', [...$payload, 'locale' => 'en'])->assertSuccessful();
});

test('the locale of existing content cannot be changed', function () {
    actingAsAdministratorWith('pages.update');
    $page = Page::factory()->create(['locale' => 'id']);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['locale' => 'en'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale');
});

test('an unsupported locale is rejected when creating content', function () {
    actingAsAdministratorWith('pages.create');

    $this->postJson('/api/v1/admin/pages', ['title' => 'Page', 'slug' => 'page', 'status' => 'draft', 'blocks' => [], 'locale' => 'fr'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale');
});

test('the admin page list can be filtered by locale and shows translations on a page', function () {
    actingAsAdministratorWith('pages.view');
    $indonesian = Page::factory()->create(['title' => 'Beranda', 'locale' => 'id']);
    Page::factory()->create(['title' => 'Home', 'locale' => 'en', 'translation_group_id' => $indonesian->translation_group_id]);

    $this->getJson('/api/v1/admin/pages?locale=en')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Home');

    $this->getJson("/api/v1/admin/pages/{$indonesian->id}")
        ->assertSuccessful()
        ->assertJsonCount(2, 'data.translations');
});

test('posts and menus accept a locale and a translation source', function () {
    actingAsAdministratorWith('posts.create', 'menus.create');
    $post = Post::factory()->create(['slug' => 'tips', 'locale' => 'id']);
    $menu = Menu::factory()->create(['location' => 'header', 'locale' => 'id']);

    $this->postJson('/api/v1/admin/posts', [
        'title' => 'Tips', 'slug' => 'tips', 'type' => 'blog', 'status' => 'draft', 'blocks' => [],
        'locale' => 'en', 'translation_of' => $post->id,
    ])->assertSuccessful()->assertJsonPath('data.translation_group_id', $post->translation_group_id);

    $this->postJson('/api/v1/admin/menus', [
        'name' => 'Main Menu', 'location' => 'header', 'locale' => 'en', 'translation_of' => $menu->id,
    ])->assertSuccessful()->assertJsonPath('data.locale', 'en');
});

test('the language switcher falls back to the target locale home page when no translation exists', function () {
    $switcher = app(AlternateUrlResolver::class)->switcher(['id' => url('/harga')]);

    expect($switcher)->toBe([
        ['locale' => 'id', 'label' => 'ID', 'name' => 'Bahasa Indonesia', 'url' => url('/harga'), 'active' => true],
        ['locale' => 'en', 'label' => 'EN', 'name' => 'English', 'url' => url('/en'), 'active' => false],
    ]);
});

test('localized route links stay in the current locale', function () {
    app()->setLocale('en');

    expect(localized_route('website.blog.index'))->toBe(url('/en/blog'))
        ->and(localized_route('home'))->toBe(url('/en'))
        ->and(localized_route('website.blog.index', [], 'id'))->toBe(url('/blog'));
});

test('the sitemap lists content under its locale prefix', function () {
    Page::factory()->published()->create(['slug' => 'pricing', 'locale' => 'en']);
    Post::factory()->published()->create(['slug' => 'registration-tips', 'type' => PostType::Blog, 'locale' => 'en']);

    $this->get('/sitemap.xml')
        ->assertSuccessful()
        ->assertSee(url('/en/pricing'), false)
        ->assertSee(url('/en/blog/registration-tips'), false);
});
