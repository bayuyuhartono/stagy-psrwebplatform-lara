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
        $content = $this->sitemap->publishedContent();
        // Published translations of the same content, so each URL can list its language siblings.
        $alternatesByGroup = $content
            ->filter(fn ($item): bool => filled($item->getAttribute('translation_group_id')))
            ->groupBy(fn ($item): string => $item::class.'|'.$item->getAttribute('translation_group_id'))
            ->map(fn ($group): array => $group->mapWithKeys(fn ($item): array => [$item->getAttribute('locale') => $this->urls->canonicalFor($item)])->sortKeys()->all());
        $entries = $content
            ->map(fn ($item): array => [
                'location' => $this->urls->canonicalFor($item),
                'last_modified' => $item->updated_at?->toAtomString(),
                'alternates' => filled($item->getAttribute('translation_group_id'))
                    ? $alternatesByGroup->get($item::class.'|'.$item->getAttribute('translation_group_id'), [])
                    : [],
            ])
            ->unique('location')
            ->values();

        // The home page is always listed, with its own lastmod and alternates when it is a CMS page.
        if (! $entries->contains('location', url('/'))) {
            $entries->prepend(['location' => url('/'), 'last_modified' => null, 'alternates' => []]);
        }

        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.$this->escape($entry['location']).'</loc>';
            if ($entry['last_modified'] !== null) {
                $lines[] = '    <lastmod>'.$this->escape($entry['last_modified']).'</lastmod>';
            }
            // A lone language version needs no alternates; with two or more, every sibling (itself included) is listed.
            if (count($entry['alternates']) > 1) {
                foreach ($entry['alternates'] as $locale => $url) {
                    $lines[] = '    <xhtml:link rel="alternate" hreflang="'.$this->escape($locale).'" href="'.$this->escape($url).'"/>';
                }
                $default = $entry['alternates'][config('localization.default')] ?? reset($entry['alternates']);
                $lines[] = '    <xhtml:link rel="alternate" hreflang="x-default" href="'.$this->escape($default).'"/>';
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
