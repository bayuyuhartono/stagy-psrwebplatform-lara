<?php

namespace App\Features\ContentBlocks\Services;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The rich_text block's content is HTML written by the CMS editor. Anything
 * that reaches storage or the page passes through this allowlist, so editors
 * keep headings, tables, alignment, images and YouTube embeds while scripts,
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

        return trim($this->sanitizer()->sanitize($html));
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
                ->allowElement('div', ['data-youtube-video'])
                ->allowElement('iframe', ['src', 'allowfullscreen'])
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeMedias()
                ->withAttributeSanitizer(new RichTextAttributeSanitizer)
                ->withMaxInputLength(self::MAX_INPUT_LENGTH),
        );
    }
}
