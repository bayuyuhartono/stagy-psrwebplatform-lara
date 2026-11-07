<?php

namespace App\Http\Middleware;

use App\Features\Localization\Services\LocaleRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private LocaleRegistry $locales) {}

    /**
     * Apply the locale a route group is served in, or — for API routes that
     * pass no locale — the `locale` query parameter, defaulting to the site default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale ??= $request->query('locale', $this->locales->default());

        if (! is_string($locale) || ! $this->locales->isSupported($locale)) {
            throw ValidationException::withMessages([
                'locale' => 'The selected locale is not supported.',
            ]);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
