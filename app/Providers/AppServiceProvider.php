<?php

namespace App\Providers;

use App\Features\Integrations\Services\PublicIntegrationResolver;
use App\Features\Localization\Services\AlternateUrlResolver;
use App\Features\Themes\Services\ThemeManager;
use App\Models\Media;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;
use App\Models\User;
use App\Observers\SitemapCacheObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Must run before any view renders — it layers the theme view paths onto Blade's finder.
        $this->app->make(ThemeManager::class)->boot();

        $this->definePasswordPolicy();

        Gate::define('dashboard.view', fn (User $user): bool => $user->hasPermission('dashboard.view'));
        Gate::define('system-settings.view', fn (User $user): bool => $user->hasPermission('system-settings.view'));
        Gate::define('system-health.view', fn (User $user): bool => $user->hasPermission('system-health.view'));

        Page::observe(SitemapCacheObserver::class);
        Post::observe(SitemapCacheObserver::class);
        Service::observe(SitemapCacheObserver::class);
        Portfolio::observe(SitemapCacheObserver::class);
        Seo::observe(SitemapCacheObserver::class);

        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('admin-api', fn (Request $request) => Limit::perMinute(120)->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip(),
        ));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('contact-form', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        View::composer('components.public.layout', $this->composePublicLayout(...));
    }

    /**
     * Back `Password::defaults()` with a real policy.
     *
     * Without this the rule falls through to a bare 8-character minimum. The
     * breach check is skipped outside production so tests and local development
     * do not depend on reaching the Have I Been Pwned API.
     */
    private function definePasswordPolicy(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(12)->letters()->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * Supply the public layout with data every page needs but no controller should have to pass.
     */
    private function composePublicLayout(ViewInstance $view): void
    {
        $settings = $view->getData()['settings'] ?? collect();
        $socialImageId = $settings->get('seo', [])['social_image_id'] ?? null;
        $themes = $this->app->make(ThemeManager::class);
        $content = $view->getData()['page'] ?? null;
        $alternates = $this->app->make(AlternateUrlResolver::class)->resolve(
            $this->app->make('request'),
            $content instanceof Model ? $content : null,
        );

        $view->with([
            'alternateUrls' => $alternates,
            'languageSwitcher' => $this->app->make(AlternateUrlResolver::class)->switcher($alternates),
            'integrations' => app(PublicIntegrationResolver::class)->enabledConfigs(),
            'defaultSocialImageUrl' => $socialImageId ? Media::query()->find($socialImageId)?->url : null,
            'theme' => $themes->settingsFor($themes->active(), $settings->get('theme', [])),
        ]);
    }
}
