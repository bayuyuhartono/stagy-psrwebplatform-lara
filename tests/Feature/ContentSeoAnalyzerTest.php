<?php

use App\Features\Seo\Services\ContentSeoAnalyzer;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\Seo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

/**
 * A well-optimized article: ~650 words, keyword in every recommended place,
 * subheadings at least every 300 words, short paragraphs, links and an image.
 *
 * @return array<string, mixed>
 */
function optimizedArticle(array $overrides = []): array
{
    $paragraph = '<p>'.str_repeat('Tamu datang dan langsung check-in tanpa antre di meja registrasi. ', 9).'</p>';

    return [
        'title' => 'Cara Membuat Buku Tamu Digital untuk Event',
        'slug' => 'buku-tamu-digital-event',
        'excerpt' => null,
        'meta_title' => 'Buku Tamu Digital: Panduan Lengkap untuk Event Korporat',
        'meta_description' => 'Pelajari cara membuat buku tamu digital untuk event korporat: dari import data tamu, undangan QR, sampai laporan kehadiran yang siap dikirim.',
        'focus_keyword' => 'buku tamu digital',
        'robots_index' => true,
        'has_cover_image' => true,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>Buku tamu digital mempercepat registrasi di pintu masuk. Baca juga <a href="/harga">paket harga</a> dan <a href="https://www.w3.org/WAI/">panduan aksesibilitas</a>.</p>'
            .'<h2>Kenapa buku tamu digital</h2>'.str_repeat($paragraph, 4)
            .'<h2>Langkah menyiapkan check-in</h2>'.str_repeat($paragraph, 4)
            .'<p>Dengan buku tamu digital, laporan kehadiran siap begitu acara selesai.</p><img src="/storage/meja.jpg" alt="Meja registrasi">']]],
        'site_name' => 'Stagy',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $result
 */
function checkStatus(array $result, string $key): string
{
    return collect($result['checks'])->firstWhere('key', $key)['status'];
}

test('a well-optimized article scores in the good band', function () {
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle());

    expect($result['rating'])->toBe('good')
        ->and($result['score'])->toBeGreaterThanOrEqual(ContentSeoAnalyzer::GOOD_SCORE)
        ->and(collect($result['checks'])->where('status', 'bad')->pluck('key')->all())->toBe([]);
});

test('an empty draft scores poor and asks for a focus keyword', function () {
    $result = app(ContentSeoAnalyzer::class)->analyze(['title' => 'Draft', 'slug' => 'draft']);

    expect($result['rating'])->toBe('poor')
        ->and(checkStatus($result, 'focus_keyword'))->toBe('bad')
        ->and(checkStatus($result, 'keyword_in_title'))->toBe('bad')
        ->and(checkStatus($result, 'word_count'))->toBe('bad');
});

test('the search title is judged by the length Google shows', function (int $length, string $status) {
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['meta_title' => str_repeat('a', $length)]));

    expect(checkStatus($result, 'seo_title_length'))->toBe($status);
})->with([
    'too short' => [19, 'bad'],
    'short' => [29, 'warning'],
    'shortest good' => [30, 'good'],
    'longest good' => [60, 'good'],
    'truncated' => [61, 'warning'],
    'far too long' => [71, 'bad'],
]);

test('the search description falls back to the excerpt and is judged on 120–160 characters', function (int $length, string $status) {
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['meta_description' => null, 'excerpt' => str_repeat('a', $length)]));

    expect(checkStatus($result, 'meta_description_length'))->toBe($status);
})->with([
    'missing' => [0, 'bad'],
    'short' => [119, 'warning'],
    'shortest good' => [120, 'good'],
    'longest good' => [160, 'good'],
    'long' => [161, 'warning'],
]);

test('word count bands follow the 300 and 600 word benchmarks', function (int $words, string $status) {
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['blocks' => [['type' => 'rich_text', 'data' => ['content' => '<p>'.str_repeat('kata ', $words).'</p>']]]]));

    expect(checkStatus($result, 'word_count'))->toBe($status);
})->with([[299, 'bad'], [300, 'warning'], [600, 'good']]);

test('keyword stuffing is flagged', function () {
    $stuffed = '<h2>Buku tamu digital</h2><p>'.str_repeat('buku tamu digital ', 20).str_repeat('kata ', 100).'</p>';
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['blocks' => [['type' => 'rich_text', 'data' => ['content' => $stuffed]]]]));

    expect(checkStatus($result, 'keyword_density'))->toBe('bad')
        ->and(collect($result['checks'])->firstWhere('key', 'keyword_density')['message'])->toContain('stuffing');
});

test('long stretches without a subheading and long paragraphs are flagged', function () {
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['blocks' => [['type' => 'rich_text', 'data' => [
        'content' => '<h2>Buku tamu digital</h2><p>'.str_repeat('kata ', 410).'</p>',
    ]]]]));

    expect(checkStatus($result, 'subheading_distribution'))->toBe('bad')
        ->and(checkStatus($result, 'paragraph_length'))->toBe('bad');
});

test('links are told apart as internal or external and images need alt text', function () {
    config(['app.url' => 'https://stagy.example']);
    $result = app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['has_cover_image' => false, 'blocks' => [['type' => 'rich_text', 'data' => [
        'content' => '<p>Lihat <a href="https://stagy.example/harga">harga</a> dan <a href="#faq">FAQ</a>.</p><img src="/storage/a.jpg" alt="">',
    ]]]]));

    expect(checkStatus($result, 'internal_links'))->toBe('good')
        ->and(checkStatus($result, 'external_links'))->toBe('warning')
        ->and(checkStatus($result, 'image'))->toBe('good')
        ->and(checkStatus($result, 'image_alt'))->toBe('bad');
});

test('a noindexed post is flagged as unable to rank', function () {
    expect(checkStatus(app(ContentSeoAnalyzer::class)->analyze(optimizedArticle(['robots_index' => false])), 'indexable'))->toBe('bad');
});

function actingAsSeoAnalysisUser(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('post editors get a live analysis of unsaved content', function () {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => true]);
    actingAsSeoAnalysisUser(['posts.view']);

    $this->postJson('/api/v1/admin/posts/seo-analysis', optimizedArticle())
        ->assertSuccessful()
        ->assertJsonPath('data.rating', 'good')
        ->assertJsonStructure(['data' => ['score', 'rating', 'word_count', 'checks' => [['key', 'group', 'status', 'message']]]]);
});

test('the live analysis needs post access and the seo module', function (array $permissions, bool $seoEnabled, int $status) {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => $seoEnabled]);
    actingAsSeoAnalysisUser($permissions);

    $this->postJson('/api/v1/admin/posts/seo-analysis', ['title' => 'Draft'])->assertStatus($status);
})->with([
    'no post permission' => [['seo.view'], true, 403],
    'seo module disabled' => [['posts.view'], false, 404],
]);

test('the stored post score uses the content analysis and follows content edits', function () {
    FeatureFlag::factory()->create(['name' => 'posts', 'enabled' => true]);
    FeatureFlag::factory()->create(['name' => 'seo', 'enabled' => true]);
    actingAsSeoAnalysisUser(['posts.update', 'posts.update.any', 'seo.view', 'seo.update']);
    $article = optimizedArticle();
    $post = Post::factory()->create(['title' => $article['title'], 'slug' => $article['slug'], 'blocks' => $article['blocks'], 'cover_image_id' => null]);

    $this->putJson("/api/v1/admin/posts/{$post->id}/seo", [
        'meta_title' => $article['meta_title'],
        'meta_description' => $article['meta_description'],
        'focus_keyword' => $article['focus_keyword'],
        'robots_index' => true,
        'robots_follow' => true,
        'twitter_card' => 'summary_large_image',
    ])->assertSuccessful();
    $scoreWithBody = Seo::query()->whereMorphedTo('seoable', $post)->value('score');

    $this->putJson("/api/v1/admin/posts/{$post->id}", ['blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Pendek.']]]])->assertSuccessful();

    expect($scoreWithBody)->toBeGreaterThan(70)
        ->and(Seo::query()->whereMorphedTo('seoable', $post)->value('score'))->toBeLessThan($scoreWithBody);
});
