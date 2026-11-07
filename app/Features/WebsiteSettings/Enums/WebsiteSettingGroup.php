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

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::General => ['site_name' => config('app.name'), 'tagline' => null, 'locale' => config('app.locale'), 'timezone' => config('app.timezone')],
            self::Branding => ['logo_id' => null, 'favicon_id' => null, 'primary_color' => null, 'secondary_color' => null],
            self::Contact => ['email' => null, 'phone' => null, 'address' => null],
            self::Social => ['facebook_url' => null, 'instagram_url' => null, 'linkedin_url' => null, 'youtube_url' => null, 'x_url' => null],
            self::Seo => ['default_title' => null, 'default_description' => null, 'social_image_id' => null],
            self::Experience => ['maintenance_mode' => false, 'announcement_enabled' => false, 'announcement' => null],
            // Seeded from the active theme's manifest defaults; see ThemeManager::settingsFor().
            self::Theme => app(ThemeManager::class)->active()->defaults(),
        };
    }
}
