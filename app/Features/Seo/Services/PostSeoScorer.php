<?php

namespace App\Features\Seo\Services;

use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Models\Post;

/**
 * A post's stored SEO score comes from the same content analysis the editor
 * shows live, so the SEO overview and the editor never disagree.
 */
class PostSeoScorer
{
    public function __construct(
        private ContentSeoAnalyzer $analyzer,
        private PublicWebsiteSettings $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $seoAttributes  The SEO record's fields (meta_title, meta_description, focus_keyword, robots_index, og_image_id).
     */
    public function score(Post $post, array $seoAttributes): int
    {
        return $this->analyzer->analyze([
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'blocks' => $post->blocks ?? [],
            'has_cover_image' => filled($post->cover_image_id) || filled($seoAttributes['og_image_id'] ?? null),
            'meta_title' => $seoAttributes['meta_title'] ?? null,
            'meta_description' => $seoAttributes['meta_description'] ?? null,
            'focus_keyword' => $seoAttributes['focus_keyword'] ?? null,
            'robots_index' => (bool) ($seoAttributes['robots_index'] ?? true),
            'site_name' => $this->settings->forCurrentLocale()->get('general', [])['site_name'] ?? config('app.name'),
        ])['score'];
    }

    /**
     * Re-score a post whose content changed. Posts without an SEO record keep none.
     */
    public function refresh(Post $post): void
    {
        $seo = $post->seo()->first();

        if ($seo !== null) {
            $seo->forceFill(['score' => $this->score($post, $seo->getAttributes())])->saveQuietly();
        }
    }
}
