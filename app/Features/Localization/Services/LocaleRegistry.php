<?php

namespace App\Features\Localization\Services;

use Illuminate\Support\Facades\App;

class LocaleRegistry
{
    public function default(): string
    {
        return config('localization.default');
    }

    /** @return list<string> */
    public function supported(): array
    {
        return array_keys(config('localization.supported'));
    }

    /** @return list<string> */
    public function prefixed(): array
    {
        return array_values(array_diff($this->supported(), [$this->default()]));
    }

    public function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->supported(), true);
    }

    public function current(): string
    {
        $locale = App::getLocale();

        return $this->isSupported($locale) ? $locale : $this->default();
    }

    public function label(string $locale): string
    {
        return config("localization.supported.{$locale}.label", strtoupper($locale));
    }

    public function name(string $locale): string
    {
        return config("localization.supported.{$locale}.name", $locale);
    }

    /**
     * The URL path prefix for a locale: empty for the default locale, "/en" otherwise.
     */
    public function pathPrefix(string $locale): string
    {
        return $locale === $this->default() ? '' : '/'.$locale;
    }

    /**
     * Prefix a public path with the locale segment it is served under.
     */
    public function localizePath(string $path, string $locale): string
    {
        $path = '/'.ltrim($path, '/');
        $prefix = $this->pathPrefix($locale);

        if ($prefix === '') {
            return $path;
        }

        return $path === '/' ? $prefix : $prefix.$path;
    }

    /**
     * The name a public route is registered under for a locale.
     */
    public function routeName(string $name, string $locale): string
    {
        return $locale === $this->default() ? $name : "{$locale}.{$name}";
    }

    /**
     * Strip a locale prefix from a route name, returning the default-locale name.
     */
    public function baseRouteName(string $name): string
    {
        foreach ($this->prefixed() as $locale) {
            if (str_starts_with($name, "{$locale}.")) {
                return substr($name, strlen($locale) + 1);
            }
        }

        return $name;
    }

    /** @return array<string, array{label: string, name: string}> */
    public function options(): array
    {
        return collect($this->supported())
            ->mapWithKeys(fn (string $locale): array => [$locale => ['label' => $this->label($locale), 'name' => $this->name($locale)]])
            ->all();
    }
}
