<?php

namespace App\Features\Themes\Services;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Models\WebsiteSetting;

/**
 * The active theme's settings (stored values over manifest defaults), for views
 * outside the layout such as content blocks. Bound as a scoped singleton so the
 * settings are read once per request.
 */
class ThemeSettings
{
    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    public function __construct(
        private ThemeManager $themes,
        private WebsiteSettingRepository $websiteSettings,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->settings === null) {
            $stored = $this->websiteSettings->public()
                ->first(fn (WebsiteSetting $setting): bool => $setting->group === WebsiteSettingGroup::Theme)
                ?->settings ?? [];
            $this->settings = $this->themes->settingsFor($this->themes->active(), $stored);
        }

        return $this->settings;
    }
}
