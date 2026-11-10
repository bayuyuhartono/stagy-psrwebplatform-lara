<?php

namespace App\Features\ContentBlocks\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Value checks the generic sanitizer cannot express: an alignment must be one
 * of the editor's options, and an iframe may only embed a YouTube player.
 */
class RichTextAttributeSanitizer implements AttributeSanitizerInterface
{
    public const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    public const EMBED_HOSTS = ['www.youtube-nocookie.com', 'youtube-nocookie.com', 'www.youtube.com', 'youtube.com'];

    /**
     * @return list<string>|null
     */
    public function getSupportedElements(): ?array
    {
        return null;
    }

    /**
     * @return list<string>|null
     */
    public function getSupportedAttributes(): ?array
    {
        return ['data-align', 'src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if ($attribute === 'data-align') {
            return in_array($value, self::ALIGNMENTS, true) ? $value : null;
        }

        if ($element === 'iframe') {
            return $this->isYouTubeEmbed($value) ? $value : null;
        }

        return $value;
    }

    private function isYouTubeEmbed(string $url): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https'
            && in_array($parts['host'] ?? null, self::EMBED_HOSTS, true)
            && str_starts_with($parts['path'] ?? '', '/embed/');
    }
}
