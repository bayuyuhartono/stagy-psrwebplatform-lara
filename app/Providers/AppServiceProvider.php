<?php

namespace App\Providers;

use App\Features\ContentBlocks\Services\ContentLinkResolver;
use App\Features\Integrations\Services\PublicIntegrationResolver;
use App\Features\Localization\Services\AlternateUrlResolver;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Pricing\Services\PricingCatalog;
use App\Features\Themes\Services\ThemeManager;
use App\Features\Themes\Services\ThemeSettings;
use App\Features\WebsiteSettings\Services\WhatsAppLink;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;
use App\Models\User;
use App\Observers\SitemapCacheObserver;
use App\Rules\ContentLink;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
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
        $this->app->scoped(ContentLinkResolver::class);
        $this->app->scoped(PricingCatalog::class);
        $this->app->scoped(ThemeSettings::class);
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
        // Switching a module on or off adds or removes its URLs.
        FeatureFlag::observe(SitemapCacheObserver::class);

        Validator::extend('content_link', fn (string $attribute, mixed $value): bool => is_string($value) && ContentLink::isValid($value), 'The :attribute must be a URL, a path starting with "/", an "#anchor", a mailto:/tel: link, or "whatsapp".');

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
        $branding = $settings->get('branding', []);
        // One query for every image the layout may show.
        $layoutMedia = Media::query()
            ->whereKey(array_filter([$settings->get('seo', [])['social_image_id'] ?? null, $branding['logo_id'] ?? null, $branding['favicon_id'] ?? null]))
            ->get()
            ->keyBy('id');
        $themes = $this->app->make(ThemeManager::class);
        $content = $view->getData()['page'] ?? null;
        $alternates = $this->app->make(AlternateUrlResolver::class)->resolve(
            $this->app->make('request'),
            $content instanceof Model ? $content : null,
        );

        $view->with([
            // Every menu the active theme renders, keyed by location (null when not set up yet).
            'menus' => collect($themes->active()->menuLocations)
                ->mapWithKeys(fn (string $label, string $location): array => [$location => $this->app->make(MenuRepository::class)->findActiveByLocationOrNull($location)])
                ->all(),
            'alternateUrls' => $alternates,
            'whatsappUrl' => $this->app->make(WhatsAppLink::class)->url(
                $settings->get('contact', [])['whatsapp'] ?? $settings->get('contact', [])['phone'] ?? null,
            ),
            'languageSwitcher' => $this->app->make(AlternateUrlResolver::class)->switcher($alternates),
            'integrations' => app(PublicIntegrationResolver::class)->enabledConfigs(),
            'defaultSocialImageUrl' => $layoutMedia->get($settings->get('seo', [])['social_image_id'] ?? '')?->url,
            // The content's own cover, the share image when its SEO names none. Only read when already eager-loaded.
            'contentImage' => $content instanceof Model && $content->relationLoaded('coverImage') ? $content->getRelation('coverImage') : null,
            // Uploaded branding; themes fall back to their own bundled logo and favicon when these are null.
            'brandLogoUrl' => $layoutMedia->get($branding['logo_id'] ?? '')?->url,
            'brandFaviconUrl' => $layoutMedia->get($branding['favicon_id'] ?? '')?->url,
            'theme' => $themes->settingsFor($themes->active(), $settings->get('theme', [])),
        ]);
    }
}
