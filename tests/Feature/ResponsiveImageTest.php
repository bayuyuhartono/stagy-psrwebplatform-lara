<?php

use App\Features\Media\Services\ResponsiveImageGenerator;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

function storeTestImage(string $path, int $width, int $height): void
{
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 20, 40, 80));
    ob_start();
    imagejpeg($image, null, 85);
    Storage::disk('public')->put($path, (string) ob_get_clean());
}

test('variants are generated for every width narrower than the source', function () {
    Storage::fake('public');
    storeTestImage('media/wide.jpg', 2000, 1000);

    $variants = app(ResponsiveImageGenerator::class)->generate('public', 'media/wide.jpg', 'image/jpeg');

    expect($variants)->toHaveCount(4)
        ->and(array_column($variants, 'width'))->toBe([320, 640, 1024, 1600]);

    foreach ($variants as $variant) {
        Storage::disk('public')->assertExists($variant['path']);
    }
});

test('the source image is never upscaled', function () {
    Storage::fake('public');
    storeTestImage('media/small.jpg', 500, 250);

    $variants = app(ResponsiveImageGenerator::class)->generate('public', 'media/small.jpg', 'image/jpeg');

    expect(array_column($variants, 'width'))->toBe([320]);
});

test('unsupported types produce no variants', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/animation.gif', 'not-really-a-gif');

    expect(app(ResponsiveImageGenerator::class)->generate('public', 'media/animation.gif', 'image/gif'))->toBe([])
        ->and(app(ResponsiveImageGenerator::class)->generate('public', 'media/doc.pdf', 'application/pdf'))->toBe([]);
});

test('an unreadable source degrades to no variants instead of throwing', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/corrupt.jpg', 'this is not image data');

    expect(app(ResponsiveImageGenerator::class)->generate('public', 'media/corrupt.jpg', 'image/jpeg'))->toBe([]);
});

test('the srcset accessor lists every variant plus the original', function () {
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'media/wide.jpg',
        'metadata' => [
            'width' => 2000,
            'height' => 1000,
            'responsive' => [
                ['width' => 320, 'height' => 160, 'path' => 'media/wide-320w.jpg'],
                ['width' => 640, 'height' => 320, 'path' => 'media/wide-640w.jpg'],
            ],
        ],
    ]);

    expect($media->srcset)
        ->toContain('media/wide-320w.jpg 320w')
        ->toContain('media/wide-640w.jpg 640w')
        ->toContain('media/wide.jpg 2000w');
});

test('media without variants has a null srcset', function () {
    $media = Media::factory()->create(['metadata' => ['width' => 100, 'height' => 100]]);

    expect($media->srcset)->toBeNull();
});

test('an image block renders a srcset and dimensions', function () {
    FeatureFlag::factory()->create(['name' => 'pages']);
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'media/inline.jpg',
        'alt_text' => 'Fallback alt',
        'metadata' => [
            'width' => 1600,
            'height' => 900,
            'responsive' => [['width' => 640, 'height' => 360, 'path' => 'media/inline-640w.jpg']],
        ],
    ]);
    Page::factory()->published()->create([
        'slug' => 'gallery',
        'blocks' => [['type' => 'image', 'data' => ['media_id' => $media->getKey(), 'alt' => 'A wide photograph', 'caption' => 'Shot on location']]],
    ]);

    $this->get('/gallery')
        ->assertSuccessful()
        ->assertSee('media/inline-640w.jpg 640w', escape: false)
        ->assertSee('width="1600"', escape: false)
        ->assertSee('A wide photograph')
        ->assertSee('Shot on location')
        ->assertSee('loading="lazy"', escape: false);
});
