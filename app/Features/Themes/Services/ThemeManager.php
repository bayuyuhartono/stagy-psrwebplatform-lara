<?php

namespace App\Features\Themes\Services;

use App\Features\Themes\DataTransferObjects\ThemeManifest;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Discovers themes on disk and resolves the active one at runtime.
 *
 * View resolution is layered by prepending directories to Blade's finder, so the
 * lookup order is: active theme → fallback theme → application resources/views.
 * A theme therefore only has to ship the views it actually wants to change.
 */
class ThemeManager
{
    private const CACHE_KEY = 'themes:manifests';

    /** @var array<string, ThemeManifest>|null */
    private ?array $manifests = null;

    public function __construct(private ViewFactory $views) {}

    /**
     * Register theme view paths with Blade. Called once on boot.
     */
    public function boot(): void
    {
        $finder = $this->views->getFinder();

        // Prepended last wins, so register the fallback before the active theme.
        foreach ([$this->fallbackSlug(), $this->activeSlug()] as $slug) {
            $path = $this->viewPath($slug);

            if ($slug !== null && File::isDirectory($path)) {
                $finder->prependLocation($path);
            }
        }
    }

    public function activeSlug(): string
    {
        return (string) config('themes.active', 'default');
    }

    public function fallbackSlug(): ?string
    {
        $fallback = config('themes.fallback');

        return $fallback === $this->activeSlug() ? null : $fallback;
    }

    public function active(): ThemeManifest
    {
        return $this->manifest($this->activeSlug())
            ?? throw new RuntimeException("Active theme [{$this->activeSlug()}] is not installed.");
    }

    public function manifest(string $slug): ?ThemeManifest
    {
        return $this->all()[$slug] ?? null;
    }

    /**
     * Every installed theme, keyed by slug.
     *
     * @return array<string, ThemeManifest>
     */
    public function all(): array
    {
        if ($this->manifests !== null) {
            return $this->manifests;
        }

        $load = fn (): array => array_map(
            fn (array $data): array => $data,
            $this->readManifests(),
        );

        $raw = config('themes.cache', true)
            ? Cache::rememberForever(self::CACHE_KEY, $load)
            : $load();

        return $this->manifests = array_map(
            fn (array $data): ThemeManifest => ThemeManifest::fromArray($data['slug'], $data),
            $raw,
        );
    }

    /**
     * Merge stored setting values over the active theme's declared defaults.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function settingsFor(ThemeManifest $manifest, array $stored): array
    {
        return [...$manifest->defaults(), ...array_intersect_key($stored, $manifest->settings)];
    }

    public function flush(): void
    {
        $this->manifests = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function viewPath(?string $slug): string
    {
        return rtrim((string) config('themes.path'), '/')."/{$slug}/views";
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readManifests(): array
    {
        $root = (string) config('themes.path');

        if (! File::isDirectory($root)) {
            return [];
        }

        $manifests = [];

        foreach (File::directories($root) as $directory) {
            $slug = basename($directory);
            $file = "{$directory}/theme.json";

            if (! File::exists($file)) {
                continue;
            }

            $decoded = json_decode(File::get($file), true);

            if (! is_array($decoded)) {
                throw new RuntimeException("Theme [{$slug}] has an invalid theme.json.");
            }

            $manifests[$slug] = [...$decoded, 'slug' => $slug];
        }

        return $manifests;
    }
}
