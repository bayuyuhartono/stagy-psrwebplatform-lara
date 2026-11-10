<?php

namespace App\Features\ContentBlocks\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Value checks the generic sanitizer cannot express: an alignment must be one
 * of the editor's options, and an iframe may only load a known provider's
 * embed player over HTTPS.
 */
class RichTextAttributeSanitizer implements AttributeSanitizerInterface
{
    public const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    /**
     * Embeddable players: provider => [allowed hosts, pattern the URL path must match].
     *
     * @var array<string, array{0: list<string>, 1: string}>
     */
    public const EMBED_PROVIDERS = [
        'youtube' => [['www.youtube-nocookie.com', 'youtube-nocookie.com', 'www.youtube.com', 'youtube.com'], '#^/embed/[\w-]+$#'],
        'vimeo' => [['player.vimeo.com'], '#^/video/\d+$#'],
        'maps' => [['www.google.com', 'maps.google.com'], '#^/maps/embed$#'],
        'spotify' => [['open.spotify.com'], '#^/embed/(track|album|playlist|episode|show|artist)/\w+$#'],
        'instagram' => [['www.instagram.com', 'instagram.com'], '#^/(p|reel)/[\w-]+/embed/?$#'],
        'tiktok' => [['www.tiktok.com'], '#^/embed/v2/\d+$#'],
    ];

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
        return ['data-align', 'data-embed', 'src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return match (true) {
            $attribute === 'data-align' => in_array($value, self::ALIGNMENTS, true) ? $value : null,
            $attribute === 'data-embed' => array_key_exists($value, self::EMBED_PROVIDERS) ? $value : null,
            $element === 'iframe' => self::embedProvider($value) !== null ? $value : null,
            default => $value,
        };
    }

    /**
     * The provider whose player the URL loads, or null when it is not an allowed embed.
     */
    public static function embedProvider(string $url): ?string
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https') {
            return null;
        }

        foreach (self::EMBED_PROVIDERS as $provider => [$hosts, $pathPattern]) {
            if (in_array($parts['host'] ?? null, $hosts, true) && preg_match($pathPattern, $parts['path'] ?? '')) {
                return $provider;
            }
        }

        return null;
    }
}
