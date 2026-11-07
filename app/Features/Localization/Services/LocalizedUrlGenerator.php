<?php

namespace App\Features\Localization\Services;

use Illuminate\Support\Facades\Route;

class LocalizedUrlGenerator
{
    public function __construct(private LocaleRegistry $locales) {}

    /**
     * Generate a URL to a public route in the given (or current) locale.
     *
     * Public routes are registered once per locale ("home", "en.home"); this
     * picks the registration for the locale so theme links stay in-language.
     *
     * @param  mixed  $parameters
     */
    public function route(string $name, $parameters = [], ?string $locale = null): string
    {
        $locale ??= $this->locales->current();
        $localizedName = $this->locales->routeName($this->locales->baseRouteName($name), $locale);

        return route(Route::has($localizedName) ? $localizedName : $name, $parameters);
    }

    public function home(?string $locale = null): string
    {
        return $this->route('home', [], $locale);
    }
}
