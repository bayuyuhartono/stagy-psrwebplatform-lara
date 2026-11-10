<?php

use App\Features\ContentBlocks\Services\RichTextContent;
use App\Models\Post;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

function richText(string $content): string
{
    return app(RichTextContent::class)->toHtml($content);
}

test('editor formatting survives sanitizing', function () {
    $html = '<h2 data-align="center">Paket</h2><p>Teks <strong>tebal</strong>, <u>garis</u>, <mark>sorot</mark> dan <a href="/harga">harga</a>.</p>'
        .'<table><tbody><tr><th colspan="2" rowspan="1"><p>Header</p></th></tr><tr><td><p>A</p></td><td><p>B</p></td></tr></tbody></table>'
        .'<img src="/storage/media/foto.jpg" alt="Meja registrasi">'
        .'<div data-youtube-video=""><iframe src="https://www.youtube-nocookie.com/embed/abc123" allowfullscreen="true"></iframe></div>';

    expect(richText($html))
        ->toContain('<h2 data-align="center">Paket</h2>')
        ->toContain('<strong>tebal</strong>')
        ->toContain('<u>garis</u>')
        ->toContain('<mark>sorot</mark>')
        ->toContain('<a href="/harga">harga</a>')
        ->toContain('<th colspan="2" rowspan="1">')
        ->toContain('<img src="/storage/media/foto.jpg" alt="Meja registrasi" />')
        ->toContain('src="https://www.youtube-nocookie.com/embed/abc123"');
});

test('scripts, event handlers, styles and unsafe urls are removed', function () {
    $html = '<p style="color:red" onclick="steal()">Halo<script>alert(1)</script></p>'
        .'<p><a href="javascript:alert(1)">klik</a><img src="x" onerror="alert(1)"></p>'
        .'<p data-align="expression(alert(1))">x</p>';

    expect(richText($html))
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('style=')
        ->not->toContain('javascript:')
        ->not->toContain('expression(')
        ->toContain('Halo');
});

test('the empty paragraph the editor keeps after a final table is dropped', function () {
    expect(richText('<table><tbody><tr><td><p>A</p></td></tr></tbody></table><p></p>'))->toEndWith('</table>');
});

test('players from the supported providers can be embedded', function (string $provider, string $source) {
    expect(richText("<div data-embed=\"{$provider}\"><iframe src=\"{$source}\" title=\"Player\"></iframe></div>"))
        ->toContain("<div data-embed=\"{$provider}\">")
        ->toContain('src="'.strtok($source, '?'))
        ->toContain('loading="lazy"');
})->with([
    'vimeo' => ['vimeo', 'https://player.vimeo.com/video/76979871'],
    'google maps' => ['maps', 'https://www.google.com/maps/embed?pb=!1m18!1m12'],
    'spotify' => ['spotify', 'https://open.spotify.com/embed/track/4uLU6hMCjMI75M1A2tKUQC'],
    'instagram' => ['instagram', 'https://www.instagram.com/p/C1a2B3c4D5e/embed'],
    'tiktok' => ['tiktok', 'https://www.tiktok.com/embed/v2/7231338487075638570'],
]);

test('iframes outside the embed allowlist lose their source', function (string $source) {
    expect(richText("<div data-embed=\"vimeo\"><iframe src=\"{$source}\"></iframe></div>"))->not->toContain('src=');
})->with([
    'another host' => 'https://evil.example/embed/abc',
    'plain http' => 'http://player.vimeo.com/video/123',
    'not a player page' => 'https://www.youtube.com/watch?v=abc',
    'a google page that is not a map' => 'https://www.google.com/search?q=x',
    'a lookalike host' => 'https://player.vimeo.com.evil.example/video/123',
]);

test('an unknown embed provider name is dropped', function () {
    expect(richText('<div data-embed="evil"><p>x</p></div>'))->not->toContain('data-embed');
});

test('markdown and plain text are converted to html', function () {
    expect(richText("## Judul\n\nIsi **tebal**.\n\n<script>alert(1)</script>"))
        ->toContain('<h2>Judul</h2>')
        ->toContain('<strong>tebal</strong>')
        ->not->toContain('<script');
    expect(richText('Full story'))->toBe('<p>Full story</p>');
});

test('saving a model stores sanitized html and leaves other blocks alone', function () {
    $post = Post::factory()->create(['blocks' => [
        ['type' => 'rich_text', 'data' => ['content' => '## Judul']],
        ['type' => 'rich_text', 'data' => ['content' => '<p onclick="x()">Aman</p>']],
        ['type' => 'call_to_action', 'data' => ['heading' => '## not markdown here']],
    ]]);

    expect($post->refresh()->blocks)
        ->{0}->data->content->toBe('<h2>Judul</h2>')
        ->{1}->data->content->toBe('<p>Aman</p>')
        ->{2}->data->heading->toBe('## not markdown here');
});

test('the migration converts stored markdown blocks', function () {
    $post = Post::factory()->create(['blocks' => []]);
    DB::table('posts')->where('id', $post->id)->update(['blocks' => json_encode([['type' => 'rich_text', 'data' => ['content' => "## Lama\n\n- satu"]]])]);

    (require database_path('migrations/2026_11_10_052419_convert_rich_text_blocks_to_html.php'))->up();

    expect($post->refresh()->blocks[0]['data']['content'])->toContain('<h2>Lama</h2>')->toContain('<li>satu</li>');
});

test('a long text is cut before the subheading nearest its middle', function () {
    $section = '<p>'.str_repeat('kata ', 150).'</p>';
    [$before, $after] = app(RichTextContent::class)->splitNearMiddle("<h2>Satu</h2>{$section}<h2>Dua</h2>{$section}<h2>Tiga</h2>{$section}");

    expect($before)->toStartWith('<h2>Satu</h2>')->not->toContain('Dua')
        ->and($after)->toStartWith('<h2>Dua</h2>')->toContain('Tiga');
});

test('short texts and texts without a clean middle are not cut', function (string $html) {
    expect(app(RichTextContent::class)->splitNearMiddle($html))->toBeNull();
})->with([
    'short' => '<h2>Satu</h2><p>'.str_repeat('kata ', 100).'</p><h2>Dua</h2><p>'.str_repeat('kata ', 100).'</p>',
    'one long paragraph' => '<p>'.str_repeat('kata ', 900).'</p>',
]);
