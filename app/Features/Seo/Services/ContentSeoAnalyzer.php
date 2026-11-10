<?php

namespace App\Features\Seo\Services;

use App\Features\ContentBlocks\Services\RichTextContent;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * On-page SEO analysis of an article: the checks the post editor shows and
 * the score stored on the post's SEO record. Thresholds follow common,
 * published benchmarks:
 *
 *  - Google shows about 50–60 title characters and 120–160 description characters.
 *  - Yoast SEO: at least 300 words, a keyword density of 0.5–3%, a subheading
 *    at least every 300 words and paragraphs of at most 150 words.
 *  - Yoast / Rank Math score bands: 80+ good, 50–79 needs work, below 50 poor.
 *
 * Each check has a weight; "good" earns it in full, "warning" half, "bad" none.
 * The score is the share of weight earned, 0–100.
 *
 * @phpstan-type Check array{key: string, group: string, status: 'good'|'warning'|'bad', message: string}
 */
class ContentSeoAnalyzer
{
    public const GOOD_SCORE = 80;

    public const FAIR_SCORE = 50;

    /** @var array<string, int> */
    private const WEIGHTS = [
        'focus_keyword' => 6, 'seo_title_length' => 10, 'meta_description_length' => 10, 'keyword_in_title' => 10,
        'keyword_in_description' => 5, 'keyword_in_slug' => 5, 'slug_length' => 3,
        'word_count' => 10, 'keyword_in_introduction' => 7, 'keyword_density' => 7, 'subheadings' => 5,
        'keyword_in_subheading' => 5, 'subheading_distribution' => 4, 'paragraph_length' => 3,
        'internal_links' => 5, 'external_links' => 3, 'image' => 5, 'image_alt' => 3, 'indexable' => 4,
    ];

    /** Block data keys that hold identifiers or URLs rather than reader-facing text. */
    private const NON_TEXT_KEYS = ['id', 'type', 'url', 'media_id', 'anchor', 'variant', 'visual', 'alt'];

    public function __construct(private RichTextContent $richText) {}

    /**
     * @param  array{title?: ?string, slug?: ?string, excerpt?: ?string, blocks?: ?array<int, mixed>, has_cover_image?: bool, meta_title?: ?string, meta_description?: ?string, focus_keyword?: ?string, robots_index?: bool, site_name?: ?string}  $input
     * @return array{score: int, rating: 'good'|'fair'|'poor', word_count: int, checks: list<Check>}
     */
    public function analyze(array $input): array
    {
        $keyword = $this->normalize((string) ($input['focus_keyword'] ?? ''));
        $seoTitle = trim((string) ($input['meta_title'] ?? '')) ?: DocumentTitle::withBrand((string) ($input['title'] ?? ''), $input['site_name'] ?? null);
        $description = trim((string) ($input['meta_description'] ?? '')) ?: trim((string) ($input['excerpt'] ?? ''));
        $slug = (string) ($input['slug'] ?? '');
        $body = $this->readBody($input['blocks'] ?? []);
        $wordCount = $this->countWords($body['text']);

        $checks = [
            ...$this->searchAppearanceChecks($keyword, $seoTitle, $description, $slug),
            ...$this->contentChecks($keyword, $body, $wordCount),
            ...$this->linkAndMediaChecks($body, (bool) ($input['has_cover_image'] ?? false)),
            $this->check('indexable', 'search', ($input['robots_index'] ?? true) ? 'good' : 'bad', ($input['robots_index'] ?? true)
                ? 'Search engines may index this post.'
                : 'Hidden from search engines (noindex), so it cannot rank.'),
        ];

        $earned = collect($checks)->sum(fn (array $check): float => self::WEIGHTS[$check['key']] * match ($check['status']) {
            'good' => 1.0,
            'warning' => 0.5,
            'bad' => 0.0,
        });
        $score = (int) round($earned / array_sum(self::WEIGHTS) * 100);

        return [
            'score' => $score,
            'rating' => $score >= self::GOOD_SCORE ? 'good' : ($score >= self::FAIR_SCORE ? 'fair' : 'poor'),
            'word_count' => $wordCount,
            'checks' => $checks,
        ];
    }

    /**
     * @return list<Check>
     */
    private function searchAppearanceChecks(string $keyword, string $seoTitle, string $description, string $slug): array
    {
        $titleLength = Str::length($seoTitle);
        $descriptionLength = Str::length($description);
        $slugLength = Str::length($slug);
        $keywordSlug = Str::slug($keyword);

        return [
            $keyword === ''
                ? $this->check('focus_keyword', 'search', 'bad', 'Set a focus keyword: the phrase people search for that this post should rank for.')
                : $this->check('focus_keyword', 'search', 'good', "Focus keyword: “{$keyword}”."),
            $this->check('seo_title_length', 'search', match (true) {
                $titleLength >= 30 && $titleLength <= 60 => 'good',
                $titleLength >= 20 && $titleLength <= 70 => 'warning',
                default => 'bad',
            }, "The search title is {$titleLength} characters; 30–60 shows in full on Google."),
            $this->check('meta_description_length', 'search', match (true) {
                $descriptionLength >= 120 && $descriptionLength <= 160 => 'good',
                $descriptionLength >= 70 && $descriptionLength <= 180 => 'warning',
                default => 'bad',
            }, $descriptionLength === 0
                ? 'Add a meta description or excerpt (120–160 characters), or Google picks a sentence itself.'
                : "The search description is {$descriptionLength} characters; aim for 120–160."),
            $this->keywordCheck('keyword_in_title', 'search', $keyword, $this->contains($seoTitle, $keyword), 'The focus keyword appears in the search title.', 'Use the focus keyword in the search title, ideally near the start.'),
            $this->keywordCheck('keyword_in_description', 'search', $keyword, $this->contains($description, $keyword), 'The focus keyword appears in the search description.', 'Use the focus keyword in the search description.'),
            $this->keywordCheck('keyword_in_slug', 'search', $keyword, $keywordSlug !== '' && Str::contains($slug, $keywordSlug), 'The URL contains the focus keyword.', 'Put the focus keyword in the URL slug.'),
            $this->check('slug_length', 'search', $slugLength <= 60 ? 'good' : ($slugLength <= 80 ? 'warning' : 'bad'), $slugLength <= 60
                ? 'The URL is short and readable.'
                : "The URL slug is {$slugLength} characters; keep it under 60 by dropping filler words."),
        ];
    }

    /**
     * @param  array{text: string, introduction: string, headings: list<string>, sections: list<int>, paragraphs: list<int>, links: list<string>, images: list<string>}  $body
     * @return list<Check>
     */
    private function contentChecks(string $keyword, array $body, int $wordCount): array
    {
        $occurrences = $keyword === '' ? 0 : $this->occurrences($body['text'], $keyword);
        $density = $wordCount > 0 ? $occurrences / $wordCount * 100 : 0;
        $longestSection = max([0, ...$body['sections']]);
        $longestParagraph = max([0, ...$body['paragraphs']]);
        $densityLabel = number_format($density, 1);

        return [
            $this->check('word_count', 'content', $wordCount >= 600 ? 'good' : ($wordCount >= 300 ? 'warning' : 'bad'), $wordCount >= 600
                ? "{$wordCount} words: enough depth to rank."
                : "{$wordCount} words. Aim for at least 300, ideally 600+ for a guide."),
            $this->keywordCheck('keyword_in_introduction', 'content', $keyword, $this->contains($body['introduction'], $keyword), 'The focus keyword appears in the first paragraph.', 'Mention the focus keyword in the first paragraph.'),
            $keyword === ''
                ? $this->check('keyword_density', 'content', 'bad', 'Set a focus keyword to check how often it is used.')
                : $this->check('keyword_density', 'content', match (true) {
                    $density >= 0.5 && $density <= 2.5 => 'good',
                    $occurrences > 0 && $density <= 3.5 => 'warning',
                    default => 'bad',
                }, match (true) {
                    $occurrences === 0 => 'The focus keyword is not used in the body.',
                    $density > 2.5 => "The focus keyword makes up {$densityLabel}% of the text ({$occurrences}×); above 2.5% reads as stuffing.",
                    default => "The focus keyword makes up {$densityLabel}% of the text ({$occurrences}×); 0.5–2.5% is natural.",
                }),
            $this->check('subheadings', 'content', $body['headings'] !== [] ? 'good' : ($wordCount < 300 ? 'warning' : 'bad'), $body['headings'] !== []
                ? 'The text is divided with subheadings.'
                : 'Add H2 subheadings so readers and search engines can scan the article.'),
            $this->keywordCheck('keyword_in_subheading', 'content', $keyword, collect($body['headings'])->contains(fn (string $heading): bool => $this->contains($heading, $keyword)), 'A subheading contains the focus keyword.', 'Use the focus keyword (or a close variant) in at least one subheading.'),
            $this->check('subheading_distribution', 'content', $longestSection <= 300 ? 'good' : ($longestSection <= 400 ? 'warning' : 'bad'), $longestSection <= 300
                ? 'No stretch of text runs longer than 300 words without a subheading.'
                : "One part runs {$longestSection} words without a subheading; add one at least every 300 words."),
            $this->check('paragraph_length', 'content', $longestParagraph <= 150 ? 'good' : ($longestParagraph <= 200 ? 'warning' : 'bad'), $longestParagraph <= 150
                ? 'Paragraphs are short enough to read comfortably.'
                : "The longest paragraph has {$longestParagraph} words; keep them under 150."),
        ];
    }

    /**
     * @param  array{text: string, introduction: string, headings: list<string>, sections: list<int>, paragraphs: list<int>, links: list<string>, images: list<string>}  $body
     * @return list<Check>
     */
    private function linkAndMediaChecks(array $body, bool $hasCoverImage): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $internal = collect($body['links'])->filter(fn (string $href): bool => (str_starts_with($href, '/') && ! str_starts_with($href, '//')) || parse_url($href, PHP_URL_HOST) === $appHost)->count();
        $external = collect($body['links'])->filter(fn (string $href): bool => preg_match('#^https?://#i', $href) === 1 && parse_url($href, PHP_URL_HOST) !== $appHost)->count();
        $missingAlt = collect($body['images'])->filter(fn (string $alt): bool => trim($alt) === '')->count();

        return [
            $this->check('internal_links', 'links', $internal > 0 ? 'good' : 'bad', $internal > 0
                ? "{$internal} link(s) to other pages on this site."
                : 'Link to at least one related page on this site (a service, pricing or another article).'),
            $this->check('external_links', 'links', $external > 0 ? 'good' : 'warning', $external > 0
                ? "{$external} link(s) to other websites."
                : 'Consider linking to a trustworthy outside source that supports the article.'),
            $this->check('image', 'links', $hasCoverImage || $body['images'] !== [] ? 'good' : 'bad', $hasCoverImage || $body['images'] !== []
                ? 'The post has an image for cards, sharing and image search.'
                : 'Add a cover image: it is shown on article cards and when the link is shared.'),
            $this->check('image_alt', 'links', $missingAlt === 0 ? 'good' : 'bad', match (true) {
                $body['images'] === [] => 'No images in the body text.',
                $missingAlt === 0 => 'Every image in the body has alternative text.',
                default => "{$missingAlt} image(s) in the body have no alternative text.",
            }),
        ];
    }

    /**
     * Everything the checks read from the body, in reading order. Rich text is
     * parsed as HTML; other blocks contribute their text as paragraphs.
     *
     * @param  array<int, mixed>|null  $blocks
     * @return array{text: string, introduction: string, headings: list<string>, sections: list<int>, paragraphs: list<int>, links: list<string>, images: list<string>}
     */
    private function readBody(?array $blocks): array
    {
        $texts = [];
        $headings = [];
        $paragraphs = [];
        $links = [];
        $images = [];
        $sections = [0];
        $introduction = null;

        $addParagraph = function (string $text) use (&$texts, &$paragraphs, &$sections, &$introduction): void {
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
            if ($text === '') {
                return;
            }
            $words = $this->countWords($text);
            $texts[] = $text;
            $paragraphs[] = $words;
            $sections[array_key_last($sections)] += $words;
            $introduction ??= $text;
        };

        foreach ($blocks ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if (($block['type'] ?? null) === RichTextContent::BLOCK_TYPE) {
                $html = $this->richText->toHtml(is_string($data['content'] ?? null) ? $data['content'] : '');
                foreach ($this->elements($html) as $element) {
                    if (in_array($element->nodeName, ['h2', 'h3', 'h4'], true)) {
                        // A subheading starts a new section for the "words between subheadings" check.
                        $headings[] = trim($element->textContent);
                        $texts[] = trim($element->textContent);
                        $sections[] = 0;
                    } elseif ($element->nodeName === 'img') {
                        $images[] = $element->getAttribute('alt');
                    } elseif ($element->nodeName === 'a') {
                        $links[] = $element->getAttribute('href');
                    } else {
                        $addParagraph($element->textContent);
                    }
                }

                continue;
            }

            if (($block['type'] ?? null) === 'image') {
                $images[] = (string) ($data['alt'] ?? '');
            }
            foreach ($this->strings($data) as $text) {
                $addParagraph($text);
            }
        }

        return [
            'text' => implode(' ', $texts),
            'introduction' => (string) $introduction,
            'headings' => $headings,
            'sections' => $sections,
            'paragraphs' => $paragraphs,
            'links' => $links,
            'images' => $images,
        ];
    }

    /**
     * Headings, text blocks (paragraphs, list items, table cells), links and images, in document order.
     *
     * @return list<DOMElement>
     */
    private function elements(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        // Text blocks are the innermost p/li/td/th/pre, so list items holding a <p> are not counted twice.
        $nodes = (new DOMXPath($document))->query('//h2 | //h3 | //h4 | //img | //a[@href] | //p | //li[not(p)] | //td[not(p)] | //th[not(p)] | //pre');

        return $nodes === false ? [] : array_values(array_filter(iterator_to_array($nodes), fn (mixed $node): bool => $node instanceof DOMElement));
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    private function strings(array $data): array
    {
        $strings = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                array_push($strings, ...$this->strings($value));
            } elseif (is_string($value) && ! in_array($key, self::NON_TEXT_KEYS, true)) {
                $strings[] = strip_tags($value);
            }
        }

        return $strings;
    }

    private function countWords(string $text): int
    {
        return preg_match_all('/[\p{L}\p{N}]+(?:[\'’-][\p{L}\p{N}]+)*/u', $text);
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', Str::lower($text)) ?? '');
    }

    private function occurrences(string $text, string $keyword): int
    {
        $pattern = '/(?<![\p{L}\p{N}])'.str_replace('\ ', '\s+', preg_quote($keyword, '/')).'(?![\p{L}\p{N}])/u';

        return (int) preg_match_all($pattern, Str::lower($text));
    }

    private function contains(string $text, string $keyword): bool
    {
        return $keyword !== '' && $this->occurrences($text, $keyword) > 0;
    }

    /**
     * @return Check
     */
    private function keywordCheck(string $key, string $group, string $keyword, bool $passes, string $good, string $bad): array
    {
        if ($keyword === '') {
            return $this->check($key, $group, 'bad', 'Set a focus keyword to check this.');
        }

        return $this->check($key, $group, $passes ? 'good' : 'bad', $passes ? $good : $bad);
    }

    /**
     * @param  'good'|'warning'|'bad'  $status
     * @return Check
     */
    private function check(string $key, string $group, string $status, string $message): array
    {
        return ['key' => $key, 'group' => $group, 'status' => $status, 'message' => $message];
    }
}
