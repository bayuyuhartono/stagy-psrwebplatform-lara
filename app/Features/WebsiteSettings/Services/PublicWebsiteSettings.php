<?php

namespace App\Features\WebsiteSettings\Services;

use App\Features\Localization\Services\LocaleRegistry;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Models\WebsiteSetting;
use Illuminate\Support\Collection;

/**
 * Public website settings as visitors see them: per-language values resolved
 * to the current locale, falling back to the default locale, then to any
 * language that has a value.
 */
class PublicWebsiteSettings
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private LocaleRegistry $locales,
    ) {}

    /**
     * Every public group's settings, keyed by group ("general", "contact", ...).
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function forCurrentLocale(): Collection
    {
        return $this->websiteSettings->public()
            ->mapWithKeys(fn (WebsiteSetting $setting): array => [$setting->group->value => $this->localize($setting)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function localize(WebsiteSetting $setting, ?string $locale = null): array
    {
        $settings = $setting->settings ?? [];

        foreach ($setting->group->localizedKeys() as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = $this->resolve($settings[$key], $locale ?? $this->locales->current());
            }
        }

        return $settings;
    }

    private function resolve(mixed $value, string $locale): ?string
    {
        if (! is_array($value)) {
            return is_string($value) && $value !== '' ? $value : null;
        }

        foreach ([$locale, $this->locales->default()] as $candidate) {
            if (filled($value[$candidate] ?? null)) {
                return $value[$candidate];
            }
        }

        return collect($value)->first(fn (mixed $text): bool => filled($text));
    }
}
