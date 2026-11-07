<?php

use App\Features\Localization\Services\LocalizedUrlGenerator;

if (! function_exists('localized_route')) {
    /**
     * Generate a URL to a public route in the given (or current) locale.
     *
     * @param  mixed  $parameters
     */
    function localized_route(string $name, $parameters = [], ?string $locale = null): string
    {
        return app(LocalizedUrlGenerator::class)->route($name, $parameters, $locale);
    }
}
