<?php

namespace App\Features\WebsiteSettings\Enums;

use App\Features\Themes\Services\ThemeManager;

enum WebsiteSettingGroup: string
{
    case General = 'general';
    case Branding = 'branding';
    case Contact = 'contact';
    case Social = 'social';
    case Seo = 'seo';
    case Experience = 'experience';
    case Theme = 'theme';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Settings written once per language. Each is stored as an object keyed by
     * locale ({"id": "...", "en": "..."}); a plain string is still accepted and
     * then serves every language.
     *
     * @return list<string>
     */
    public function localizedKeys(): array
    {
        return match ($this) {
            self::General => ['tagline'],
            self::Contact => ['business_hours'],
            self::Seo => ['default_title', 'default_description'],
            self::Experience => ['announcement'],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::General => ['site_name' => config('app.name'), 'tagline' => null, 'locale' => config('app.locale'), 'timezone' => config('app.timezone')],
            self::Branding => ['logo_id' => null, 'favicon_id' => null, 'primary_color' => null, 'secondary_color' => null],
            self::Contact => ['email' => null, 'phone' => null, 'whatsapp' => null, 'address' => null, 'business_hours' => null],
            self::Social => ['facebook_url' => null, 'instagram_url' => null, 'linkedin_url' => null, 'tiktok_url' => null, 'youtube_url' => null, 'x_url' => null],
            self::Seo => ['default_title' => null, 'default_description' => null, 'social_image_id' => null],
            self::Experience => ['maintenance_mode' => false, 'announcement_enabled' => false, 'announcement' => null],
            // Seeded from the active theme's manifest defaults; see ThemeManager::settingsFor().
            self::Theme => app(ThemeManager::class)->active()->defaults(),
        };
    }
}
