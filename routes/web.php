<?php

use App\Features\Localization\Services\LocaleRegistry;
use App\Http\Controllers\Cms\AuditLogController as CmsAuditLogController;
use App\Http\Controllers\Cms\AuthController as CmsAuthController;
use App\Http\Controllers\Cms\ContactMessageController as CmsContactMessageController;
use App\Http\Controllers\Cms\DashboardController as CmsDashboardController;
use App\Http\Controllers\Cms\MediaController as CmsMediaController;
use App\Http\Controllers\Cms\MenuController as CmsMenuController;
use App\Http\Controllers\Cms\PageController as CmsPageController;
use App\Http\Controllers\Cms\PortfolioController as CmsPortfolioController;
use App\Http\Controllers\Cms\PostController as CmsPostController;
use App\Http\Controllers\Cms\SeoController as CmsSeoController;
use App\Http\Controllers\Cms\ServiceController as CmsServiceController;
use App\Http\Controllers\Cms\SettingsController as CmsSettingsController;
use App\Http\Controllers\Cms\TaxonomyController as CmsTaxonomyController;
use App\Http\Controllers\Cms\UserRoleController as CmsUserRoleController;
use App\Http\Controllers\Public\ContactPageController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PortfolioPageController;
use App\Http\Controllers\Public\PostPageController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\ServicePageController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('sitemap.xml', SitemapController::class)
    ->middleware(['throttle:public-api', 'feature:seo'])
    ->name('seo.sitemap');
Route::get('robots.txt', RobotsController::class)
    ->middleware('throttle:public-api')
    ->name('seo.robots');

/*
| Public website routes are registered once per supported locale. The default
| locale is served at the root under the plain route names ("home"); every other
| locale is served under its own prefix with prefixed names ("en.home").
*/
$publicWebsite = function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::controller(ServicePageController::class)->middleware('feature:services')->group(function (): void {
        Route::get('services', 'index')->name('website.services.index');
        Route::get('services/{slug}', 'show')->name('website.services.show');
    });
    Route::controller(PortfolioPageController::class)->middleware('feature:portfolio')->group(function (): void {
        Route::get('portfolio', 'index')->name('website.portfolio.index');
        Route::get('portfolio/{slug}', 'show')->name('website.portfolio.show');
    });
    Route::controller(PostPageController::class)->middleware('feature:posts')->group(function (): void {
        Route::get('blog', 'blog')->name('website.blog.index');
        // Archive routes must precede the {slug} catch-alls or "category" is read as a post slug.
        Route::get('blog/category/{slug}', 'blogCategory')->name('website.blog.category');
        Route::get('blog/tag/{slug}', 'blogTag')->name('website.blog.tag');
        Route::get('blog/{slug}', 'blogPost')->name('website.blog.show');
        Route::get('news', 'news')->name('website.news.index');
        Route::get('news/category/{slug}', 'newsCategory')->name('website.news.category');
        Route::get('news/tag/{slug}', 'newsTag')->name('website.news.tag');
        Route::get('news/{slug}', 'newsPost')->name('website.news.show');
    });
    Route::get('contact', [ContactPageController::class, 'show'])->name('website.contact');
};

$locales = app(LocaleRegistry::class);

Route::middleware('locale:'.$locales->default())->group($publicWebsite);

Route::middleware('guest')->prefix('cms')->name('cms.')->group(function (): void {
    Route::get('login', [CmsAuthController::class, 'create'])->name('login');
    Route::post('login', [CmsAuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->prefix('cms')->name('cms.')->group(function (): void {
    Route::get('/', CmsDashboardController::class)->name('dashboard');
    Route::middleware('feature:pages')->group(function (): void {
        Route::get('pages', [CmsPageController::class, 'index'])->name('pages.index');
        Route::get('pages/create', [CmsPageController::class, 'create'])->name('pages.create');
        Route::get('pages/{page}/edit', [CmsPageController::class, 'edit'])->name('pages.edit');
    });
    Route::get('media', CmsMediaController::class)
        ->middleware('feature:media')
        ->name('media.index');
    Route::middleware('feature:posts')->group(function (): void {
        Route::get('posts', [CmsPostController::class, 'index'])->name('posts.index');
        Route::get('posts/create', [CmsPostController::class, 'create'])->name('posts.create');
        Route::get('posts/{post}/edit', [CmsPostController::class, 'edit'])->name('posts.edit');
    });
    Route::middleware('feature:services')->group(function (): void {
        Route::get('services', [CmsServiceController::class, 'index'])->name('services.index');
        Route::get('services/create', [CmsServiceController::class, 'create'])->name('services.create');
        Route::get('services/{service}/edit', [CmsServiceController::class, 'edit'])->name('services.edit');
    });
    Route::middleware('feature:portfolio')->group(function (): void {
        Route::get('portfolio', [CmsPortfolioController::class, 'index'])->name('portfolio.index');
        Route::get('portfolio/create', [CmsPortfolioController::class, 'create'])->name('portfolio.create');
        Route::get('portfolio/{portfolio}/edit', [CmsPortfolioController::class, 'edit'])->name('portfolio.edit');
    });
    Route::get('menus', CmsMenuController::class)
        ->middleware('feature:menus')
        ->name('menus.index');
    Route::get('messages', CmsContactMessageController::class)
        ->middleware('feature:contact-messages')
        ->name('messages.index');
    Route::get('users-roles', CmsUserRoleController::class)->name('users-roles.index');
    Route::get('taxonomies', CmsTaxonomyController::class)->name('taxonomies.index');
    Route::get('settings', CmsSettingsController::class)->name('settings.index');
    Route::get('seo', CmsSeoController::class)->middleware('feature:seo')->name('seo.index');
    Route::get('audit-logs', CmsAuditLogController::class)->name('audit-logs.index');
    Route::post('logout', [CmsAuthController::class, 'destroy'])->name('logout');
});

foreach ($locales->prefixed() as $locale) {
    Route::prefix($locale)->name("{$locale}.")->middleware("locale:{$locale}")->group(function () use ($publicWebsite): void {
        $publicWebsite();
        Route::get('{slug}', [PageController::class, 'show'])
            ->where('slug', '.*')
            ->middleware('feature:pages')
            ->name('website.pages.show');
    });
}

// The root catch-all must not swallow the reserved prefixes or another locale's pages.
$reservedPrefixes = implode('|', ['cms', 'api', 'sitemap\\.xml', 'robots\\.txt', ...$locales->prefixed()]);

Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', "^(?!(?:{$reservedPrefixes})(?:/|$)).*$")
    ->middleware(['locale:'.$locales->default(), 'feature:pages'])
    ->name('website.pages.show');
