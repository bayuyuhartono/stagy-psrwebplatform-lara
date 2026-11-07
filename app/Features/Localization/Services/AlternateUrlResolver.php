<?php

namespace App\Features\Localization\Services;

use App\Features\Localization\Concerns\HasTranslations;
use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AlternateUrlResolver
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocalizedUrlGenerator $urls,
        private PublicUrlGenerator $contentUrls,
    ) {}

    /**
     * The URL of the current page in every locale it exists in, keyed by locale.
     *
     * Translatable content links to the translations loaded on it (the public
     * repositories load only published ones). Parameterless listings such as
     * the blog index exist in every locale. Anything else — a category archive,
     * a page with no translations — only exists in the current locale.
     *
     * @return array<string, string>
     */
    public function resolve(Request $request, ?Model $content): array
    {
        $current = $this->locales->current();

        if ($content !== null && in_array(HasTranslations::class, class_uses_recursive($content), true)) {
            $translations = $content->relationLoaded('translations') ? $content->translations : collect([$content]);

            return $translations
                ->filter(fn (Model $translation): bool => $this->locales->isSupported($translation->getAttribute('locale')))
                ->mapWithKeys(fn (Model $translation): array => [$translation->getAttribute('locale') => $this->contentUrls->for($translation)])
                ->sortKeys()
                ->all();
        }

        $route = $request->route();

        if ($route?->getName() !== null && $route->parameters() === []) {
            return collect($this->locales->supported())
                ->mapWithKeys(fn (string $locale): array => [$locale => $this->urls->route($route->getName(), [], $locale)])
                ->all();
        }

        return [$current => $request->url()];
    }

    /**
     * Entries for the language switcher: every supported locale, linking to the
     * current page's translation or, when there is none, that locale's home page.
     *
     * @param  array<string, string>  $alternates
     * @return list<array{locale: string, label: string, name: string, url: string, active: bool}>
     */
    public function switcher(array $alternates): array
    {
        $current = $this->locales->current();

        return collect($this->locales->supported())
            ->map(fn (string $locale): array => [
                'locale' => $locale,
                'label' => $this->locales->label($locale),
                'name' => $this->locales->name($locale),
                'url' => $alternates[$locale] ?? $this->urls->home($locale),
                'active' => $locale === $current,
            ])
            ->all();
    }
}
