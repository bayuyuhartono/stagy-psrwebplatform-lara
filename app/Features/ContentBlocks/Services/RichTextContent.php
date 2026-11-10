<?php

namespace App\Features\ContentBlocks\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The rich_text block's content is HTML written by the CMS editor. Anything
 * that reaches storage or the page passes through this allowlist, so editors
 * keep headings, tables, alignment, images and provider embeds while scripts,
 * event handlers, inline styles and unsafe URLs are removed.
 *
 * Content that is not HTML (blocks written as Markdown before the editor
 * stored HTML, or plain text sent through the API) is converted first.
 */
class RichTextContent
{
    public const BLOCK_TYPE = 'rich_text';

    /** Leaves room for the markup around content the block rules cap at 200,000 characters. */
    private const MAX_INPUT_LENGTH = 500_000;

    private ?HtmlSanitizer $sanitizer = null;

    public function toHtml(?string $content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return '';
        }

        $html = $this->isHtml($content)
            ? $content
            : Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        // The editor always keeps an empty paragraph after a final table, image or video; it would only add space.
        return preg_replace('#(?:\s*<p>(?:\s|<br\s*/?>)*</p>)+$#', '', trim($this->sanitizer()->sanitize($html)));
    }

    /**
     * Safe HTML for a page. Tables get a scroll wrapper so a wide table scrolls
     * sideways on phones instead of stretching the layout; the wrapper is added
     * only here, never stored, so the editor never sees it.
     */
    public function render(?string $content): string
    {
        return str_replace(['<table>', '</table>'], ['<div data-table-scroll><table>', '</table></div>'], $this->toHtml($content));
    }

    /**
     * Rendered HTML cut in two near the middle of the text, for placing something
     * (a "read also" suggestion) mid-article. It cuts before the subheading
     * closest to the halfway point, else between paragraphs, and only between
     * 30% and 70% of the words so the insert never sits at either end.
     *
     * @return array{0: string, 1: string}|null Null for short texts or when no clean cut exists.
     */
    public function splitNearMiddle(?string $content, int $minimumWords = 400): ?array
    {
        $html = $this->render($content);
        if ($html === '') {
            return null;
        }

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $nodes = array_values(array_filter(
            iterator_to_array($document->getElementsByTagName('body')->item(0)?->childNodes ?? []),
            fn (DOMNode $node): bool => $node instanceof DOMElement,
        ));
        $words = array_map(fn (DOMElement $node): int => preg_match_all('/[\p{L}\p{N}]+/u', $node->textContent), $nodes);
        $total = array_sum($words);

        if ($total < $minimumWords || count($nodes) < 2) {
            return null;
        }

        $best = null;
        $before = 0;
        foreach ($nodes as $index => $node) {
            if ($index > 0 && $before >= $total * 0.3 && $before <= $total * 0.7) {
                // A cut before a subheading reads naturally; between two paragraphs is the fallback.
                $quality = match (true) {
                    in_array($node->nodeName, ['h2', 'h3'], true) => 2,
                    $node->nodeName === 'p' && $nodes[$index - 1]->nodeName === 'p' => 1,
                    default => 0,
                };
                $distance = abs($before - $total / 2);
                if ($quality > 0 && ($best === null || [$quality, -$distance] > [$best['quality'], -$best['distance']])) {
                    $best = ['index' => $index, 'quality' => $quality, 'distance' => $distance];
                }
            }
            $before += $words[$index];
        }

        if ($best === null) {
            return null;
        }

        $serialize = fn (array $part): string => implode('', array_map(fn (DOMElement $node): string => (string) $document->saveHTML($node), $part));

        return [$serialize(array_slice($nodes, 0, $best['index'])), $serialize(array_slice($nodes, $best['index']))];
    }

    /**
     * The editor always opens its output with a block-level element; Markdown
     * and plain text never do.
     */
    public function isHtml(string $content): bool
    {
        return (bool) preg_match('/^<(p|h[1-6]|ul|ol|blockquote|table|figure|div|pre|hr|img)[\s>\/]/i', ltrim($content));
    }

    /**
     * Sanitize the content of every rich_text block, leaving other blocks untouched.
     *
     * @param  array<int, mixed>  $blocks
     * @return array<int, mixed>
     */
    public function normalizeBlocks(array $blocks): array
    {
        return array_map(function (mixed $block): mixed {
            if (! is_array($block) || ($block['type'] ?? null) !== self::BLOCK_TYPE || ! is_string($block['data']['content'] ?? null)) {
                return $block;
            }

            $block['data']['content'] = $this->toHtml($block['data']['content']);

            return $block;
        }, $blocks);
    }

    private function sanitizer(): HtmlSanitizer
    {
        return $this->sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowElement('p', ['data-align'])
                ->allowElement('h2', ['data-align'])
                ->allowElement('h3', ['data-align'])
                ->allowElement('h4', ['data-align'])
                ->allowElement('br')
                ->allowElement('strong')
                ->allowElement('b')
                ->allowElement('em')
                ->allowElement('i')
                ->allowElement('u')
                ->allowElement('s')
                ->allowElement('mark')
                ->allowElement('code')
                ->allowElement('pre')
                ->allowElement('hr')
                ->allowElement('a', ['href', 'title'])
                ->allowElement('ul')
                ->allowElement('ol', ['start'])
                ->allowElement('li')
                ->allowElement('blockquote')
                ->allowElement('table')
                ->allowElement('colgroup')
                ->allowElement('col')
                ->allowElement('thead')
                ->allowElement('tbody')
                ->allowElement('tr')
                ->allowElement('th', ['colspan', 'rowspan'])
                ->allowElement('td', ['colspan', 'rowspan'])
                ->allowElement('img', ['src', 'alt', 'title'])
                ->allowElement('div', ['data-youtube-video', 'data-embed'])
                ->allowElement('iframe', ['src', 'title', 'allowfullscreen'])
                ->forceAttribute('iframe', 'loading', 'lazy')
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeMedias()
                ->withAttributeSanitizer(new RichTextAttributeSanitizer)
                ->withMaxInputLength(self::MAX_INPUT_LENGTH),
        );
    }
}
