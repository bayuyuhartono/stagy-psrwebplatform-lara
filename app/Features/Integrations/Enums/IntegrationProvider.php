<?php

namespace App\Features\Integrations\Enums;

enum IntegrationProvider: string
{
    case GoogleAnalytics = 'google-analytics';
    case GoogleSearchConsole = 'google-search-console';
    case GoogleTagManager = 'google-tag-manager';
    case MetaPixel = 'meta-pixel';
    case MicrosoftClarity = 'microsoft-clarity';
    case GoogleMaps = 'google-maps';
    case Recaptcha = 'recaptcha';
    case CustomScripts = 'custom-scripts';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<string> */
    public function publicKeys(): array
    {
        return match ($this) {
            self::GoogleAnalytics => ['measurement_id'],
            self::GoogleSearchConsole => ['verification_code'],
            self::GoogleTagManager => ['container_id'],
            self::MetaPixel => ['pixel_id'],
            self::MicrosoftClarity => ['project_id'],
            self::GoogleMaps => ['api_key'],
            self::Recaptcha => ['site_key'],
            self::CustomScripts => ['head_html', 'body_html'],
        };
    }

    /** @return list<string> */
    public function secretKeys(): array
    {
        return match ($this) {
            self::Recaptcha => ['secret_key'],
            default => [],
        };
    }

    /** @return array<string, null> */
    public function defaultPublicConfig(): array
    {
        return array_fill_keys($this->publicKeys(), null);
    }
}
