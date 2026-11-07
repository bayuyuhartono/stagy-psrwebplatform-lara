<?php

namespace App\Features\Media\Services;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Derives downscaled variants of an uploaded raster image so public pages can
 * serve a srcset instead of the full-size original.
 *
 * Uses GD directly — it ships with PHP and is already a hard requirement of the
 * dimension probing in MediaService, so this adds no new dependency. Failures are
 * logged and swallowed: a missing variant degrades to the original, and an upload
 * must never fail because a derivative could not be written.
 */
class ResponsiveImageGenerator
{
    private const SUPPORTED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @return list<array{width: int, height: int, path: string}>
     */
    public function generate(string $disk, string $path, string $mimeType): array
    {
        if (! config('media.responsive.enabled', true) || ! in_array($mimeType, self::SUPPORTED_MIMES, true)) {
            return [];
        }

        $storage = Storage::disk($disk);

        try {
            $source = $this->readImage($storage->get($path), $mimeType);
        } catch (\Throwable $exception) {
            Log::warning('Responsive variants skipped: source image unreadable.', [
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }

        if ($source === null) {
            return [];
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $variants = [];

        foreach ((array) config('media.responsive.widths', []) as $targetWidth) {
            // Never upscale — a variant wider than the original is wasted bytes.
            if ($targetWidth >= $originalWidth) {
                continue;
            }

            $targetHeight = (int) round($originalHeight * ($targetWidth / $originalWidth));
            $variantPath = $this->variantPath($path, $targetWidth);

            try {
                $resized = imagescale($source, $targetWidth, $targetHeight);

                if ($resized === false) {
                    continue;
                }

                $storage->put($variantPath, $this->encode($resized, $mimeType));

                $variants[] = ['width' => $targetWidth, 'height' => $targetHeight, 'path' => $variantPath];
            } catch (\Throwable $exception) {
                Log::warning('Responsive variant could not be written.', [
                    'path' => $variantPath,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $variants;
    }

    /**
     * Remove every derived file for a media path.
     *
     * @param  list<array{width: int, height: int, path: string}>  $variants
     */
    public function purge(string $disk, array $variants): void
    {
        $paths = array_column($variants, 'path');

        if ($paths !== []) {
            Storage::disk($disk)->delete($paths);
        }
    }

    private function variantPath(string $path, int $width): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $withoutExtension = $extension === '' ? $path : substr($path, 0, -(strlen($extension) + 1));

        return "{$withoutExtension}-{$width}w".($extension === '' ? '' : ".{$extension}");
    }

    private function readImage(string $contents, string $mimeType): ?GdImage
    {
        $image = imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    private function encode(GdImage $image, string $mimeType): string
    {
        $quality = (int) config('media.responsive.quality', 82);

        ob_start();

        match ($mimeType) {
            'image/png' => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, $quality),
            default => imagejpeg($image, null, $quality),
        };

        return (string) ob_get_clean();
    }
}
