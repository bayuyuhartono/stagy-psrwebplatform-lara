<?php

namespace App\Features\Seo\Services;

use App\Features\Seo\Repositories\SitemapRepository;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    public const CACHE_KEY = 'seo:sitemap.xml';

    public function __construct(
        private SitemapRepository $sitemap,
        private PublicUrlGenerator $urls,
    ) {}

    public function xml(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), fn (): string => $this->buildXml());
    }

    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function buildXml(): string
    {
        $entries = $this->sitemap->publishedContent()
            ->map(fn ($content): array => [
                'location' => $this->urls->canonicalFor($content),
                'last_modified' => $content->updated_at?->toAtomString(),
            ])
            ->prepend(['location' => url('/'), 'last_modified' => null])
            ->unique('location')
            ->values();
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->escape($entry['location']).'</loc>';
            if ($entry['last_modified'] !== null) {
                $lines[] = '    <lastmod>'.$this->escape($entry['last_modified']).'</lastmod>';
            }
            $lines[] = '  </url>';
        }

        return implode("\n", [...$lines, '</urlset>']);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
