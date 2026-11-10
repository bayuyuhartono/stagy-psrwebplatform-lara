<?php

use App\Features\Posts\Enums\PostType;
use App\Features\Posts\Services\ReadingTimeEstimator;
use App\Features\Themes\Services\ThemeManager;
use App\Models\Category;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();

    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function blogPost(array $attributes = []): Post
{
    return Post::factory()->published()->create(['type' => PostType::Blog, 'locale' => 'id', ...$attributes]);
}

test('reading time counts the words in the excerpt and block text', function () {
    $post = Post::factory()->make([
        'excerpt' => str_repeat('kata ', 100),
        'blocks' => [
            ['id' => '7b2b8d0e-0000-4000-8000-000000000000', 'type' => 'rich_text', 'data' => ['content' => str_repeat('tamu ', 350)]],
            ['type' => 'call_to_action', 'data' => ['heading' => 'Minta demo', 'url' => 'https://example.com/a/very/long/url']],
        ],
    ]);

    expect(app(ReadingTimeEstimator::class)->minutes($post))->toBe(3)
        ->and(app(ReadingTimeEstimator::class)->minutes(Post::factory()->make(['excerpt' => null, 'blocks' => []])))->toBe(1);
});

test('the blog listing features the newest featured post and lists the rest', function () {
    blogPost(['title' => 'Artikel Lama Unggulan', 'is_featured' => true, 'published_at' => now()->subDays(10)]);
    blogPost(['title' => 'Artikel Terbaru', 'published_at' => now()->subDay()]);

    $content = $this->get('/blog')->assertSuccessful()->assertSee('Terbaru')->getContent();

    expect(substr_count($content, 'Artikel Lama Unggulan'))->toBe(1)
        ->and(strpos($content, 'Artikel Lama Unggulan'))->toBeLessThan(strpos($content, 'Artikel Terbaru'));
});

test('without a featured post the newest post heads the listing', function () {
    blogPost(['title' => 'Kemarin', 'published_at' => now()->subDay()]);
    blogPost(['title' => 'Minggu lalu', 'published_at' => now()->subWeek()]);

    $content = $this->get('/blog')->assertSuccessful()->getContent();

    expect(strpos($content, 'Kemarin'))->toBeLessThan(strpos($content, 'Minggu lalu'));
});

test('category tabs list categories with posts and mark the active one', function () {
    $guides = Category::factory()->create(['name' => 'Panduan', 'slug' => 'panduan', 'locale' => 'id']);
    Category::factory()->create(['name' => 'Kosong', 'slug' => 'kosong', 'locale' => 'id']);
    blogPost(['title' => 'Cara membuat buku tamu digital'])->categories()->attach($guides);
    blogPost(['title' => 'Checklist H-7']);

    $this->get('/blog')->assertSuccessful()->assertSee('Panduan')->assertDontSee('>Kosong<', false);

    $this->get('/blog/category/panduan')
        ->assertSuccessful()
        ->assertSeeInOrder(['aria-current="page"', 'Panduan'], false)
        ->assertSee('Cara membuat buku tamu digital')
        ->assertDontSee('Checklist H-7');
});

test('the CMS page with the listing slug supplies the blog heading and closing blocks', function () {
    Page::factory()->published()->create(['slug' => 'blog', 'locale' => 'id', 'blocks' => [
        ['type' => 'page_header', 'data' => ['eyebrow' => 'Blog', 'heading' => 'Tips untuk EO dari CMS']],
        ['type' => 'call_to_action', 'data' => ['heading' => 'Siap mencobanya di event berikutnya?', 'label' => 'Minta Demo', 'url' => '/demo']],
    ]]);
    blogPost(['title' => 'Satu artikel']);

    $content = $this->get('/blog')->assertSuccessful()->getContent();

    expect($content)->toContain('Tips untuk EO dari CMS')
        ->and(strpos($content, 'Satu artikel'))->toBeLessThan(strpos($content, 'Siap mencobanya di event berikutnya?'));
});

test('the blog listing paginates', function () {
    foreach (range(1, 12) as $day) {
        blogPost(['title' => "Artikel ke-{$day}", 'published_at' => now()->subDays($day)]);
    }

    $this->get('/blog')->assertSuccessful()->assertSee('aria-label="Halaman berikutnya"', false);
    $this->get('/blog?page=2')->assertSuccessful()->assertSee('Artikel ke-12')->assertDontSee('>Terbaru<', false);
});

test('an article shows its meta, markdown body, share links and related posts', function () {
    $guides = Category::factory()->create(['name' => 'Panduan', 'slug' => 'panduan', 'locale' => 'id']);
    $post = blogPost([
        'title' => 'Cara membuat buku tamu digital',
        'slug' => 'buku-tamu-digital',
        'published_at' => '2026-11-03 09:00:00',
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => "## 1. Rapikan daftar tamu\n\nMinta daftar tamu dalam **satu file** Excel.\n\n- Satu titik scan\n- Layar welcome\n\n<script>alert(1)</script>"]]],
    ]);
    $post->categories()->attach($guides);
    blogPost(['title' => 'Artikel sekategori', 'published_at' => now()->subMonths(2)])->categories()->attach($guides);
    blogPost(['title' => 'Artikel lain yang lebih baru']);

    $content = $this->get('/blog/buku-tamu-digital')
        ->assertSuccessful()
        ->assertSee('3 Nov 2026')
        ->assertSee('1 menit baca')
        ->assertSee('<h2>1. Rapikan daftar tamu</h2>', false)
        ->assertSee('<strong>satu file</strong>', false)
        ->assertSee('<li>Layar welcome</li>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('href="https://wa.me/?text=', false)
        ->assertSee('linkedin.com/sharing/share-offsite/?url='.rawurlencode(url('/blog/buku-tamu-digital')), false)
        ->assertSee('data-copy-link="'.url('/blog/buku-tamu-digital').'"', false)
        ->assertSee('Artikel terbaru lainnya')
        ->getContent();

    expect(strpos($content, 'Artikel sekategori'))->toBeLessThan(strpos($content, 'Artikel lain yang lebih baru'));
});

test('an article uses its cover image when one is set', function () {
    $cover = Media::factory()->create(['alt_text' => 'Meja registrasi']);
    blogPost(['slug' => 'dengan-cover', 'cover_image_id' => $cover->id]);

    $this->get('/blog/dengan-cover')->assertSuccessful()->assertSee('alt="Meja registrasi"', false);
});

test('administrators can mark a post as featured and give it a cover', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => 'posts.create']));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);
    $cover = Media::factory()->create();

    $this->postJson('/api/v1/admin/posts', [
        'title' => 'Checklist H-7', 'slug' => 'checklist-h-7', 'type' => 'blog', 'status' => 'draft', 'blocks' => [],
        'is_featured' => true, 'cover_image_id' => $cover->id,
    ])->assertSuccessful()
        ->assertJsonPath('data.is_featured', true)
        ->assertJsonPath('data.cover_image.id', $cover->id)
        ->assertJsonPath('data.reading_time_minutes', 1);
});

test('an article body is sanitized again on output and wide tables get a scroll wrapper', function () {
    $post = blogPost(['slug' => 'tabel-paket']);
    // Written past the model, as an old row or a direct database edit would be.
    DB::table('posts')->where('id', $post->id)->update(['blocks' => json_encode([['type' => 'rich_text', 'data' => [
        'content' => '<table><tbody><tr><th><p>Paket</p></th></tr></tbody></table><p onclick="steal()">Akhir<script>alert(1)</script></p>',
    ]]])]);

    $this->get('/blog/tabel-paket')
        ->assertSuccessful()
        ->assertSee('<div data-table-scroll><table>', false)
        ->assertSee('<p>Akhir</p>', false)
        ->assertDontSee('onclick="steal()"', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('a long article suggests its closest related article mid-way and lists the others below', function () {
    $guides = Category::factory()->create(['name' => 'Panduan', 'slug' => 'panduan', 'locale' => 'id']);
    $section = '<p>'.str_repeat('Tamu datang lalu check-in. ', 40).'</p>';
    blogPost(['slug' => 'panjang', 'blocks' => [['type' => 'rich_text', 'data' => ['content' => "<h2>Satu</h2>{$section}<h2>Dua</h2>{$section}<h2>Tiga</h2>{$section}"]]]])->categories()->attach($guides);
    blogPost(['title' => 'Artikel sekategori', 'published_at' => now()->subMonths(3)])->categories()->attach($guides);
    foreach (['Lain satu', 'Lain dua', 'Lain tiga'] as $index => $title) {
        blogPost(['title' => $title, 'published_at' => now()->subDays($index + 1)]);
    }

    $content = $this->get('/blog/panjang')->assertSuccessful()->getContent();
    $readAlso = strpos($content, 'Baca juga');

    expect($readAlso)->toBeGreaterThan(strpos($content, '>Satu</h2>'))
        ->and($readAlso)->toBeLessThan(strpos($content, '>Dua</h2>'))
        ->and(substr_count($content, 'Artikel sekategori'))->toBe(1)
        ->and($content)->toContain('Lain satu')->toContain('Lain dua')->toContain('Lain tiga');
});

test('a short article has no mid-way suggestion and lists three related articles', function () {
    blogPost(['slug' => 'pendek', 'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>Singkat saja.</p>']]]]);
    foreach (['Lain satu', 'Lain dua', 'Lain tiga', 'Lain empat'] as $index => $title) {
        blogPost(['title' => $title, 'published_at' => now()->subDays($index + 1)]);
    }

    $this->get('/blog/pendek')->assertSuccessful()
        ->assertDontSee('Baca juga')
        ->assertSee('Lain tiga')
        ->assertDontSee('Lain empat');
});
