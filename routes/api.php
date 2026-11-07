<?php

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\AuthTokenController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\V1\Admin\ContentBlockSchemaController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\EmailSettingController;
use App\Http\Controllers\Api\V1\Admin\FeatureFlagController;
use App\Http\Controllers\Api\V1\Admin\GlobalSearchController;
use App\Http\Controllers\Api\V1\Admin\IntegrationController as AdminIntegrationController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Api\V1\Admin\MenuItemController;
use App\Http\Controllers\Api\V1\Admin\PageController as AdminPageController;
use App\Http\Controllers\Api\V1\Admin\PageSeoController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\PortfolioController as AdminPortfolioController;
use App\Http\Controllers\Api\V1\Admin\PortfolioSeoController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\PostSeoController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\V1\Admin\ServiceSeoController;
use App\Http\Controllers\Api\V1\Admin\SystemController;
use App\Http\Controllers\Api\V1\Admin\TagController;
use App\Http\Controllers\Api\V1\Admin\ThemeController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\WebsiteSettingController as AdminWebsiteSettingController;
use App\Http\Controllers\Api\V1\Public\ContactMessageController as PublicContactMessageController;
use App\Http\Controllers\Api\V1\Public\IntegrationController as PublicIntegrationController;
use App\Http\Controllers\Api\V1\Public\MenuController as PublicMenuController;
use App\Http\Controllers\Api\V1\Public\PageController as PublicPageController;
use App\Http\Controllers\Api\V1\Public\PortfolioController as PublicPortfolioController;
use App\Http\Controllers\Api\V1\Public\PostController as PublicPostController;
use App\Http\Controllers\Api\V1\Public\ServiceController as PublicServiceController;
use App\Http\Controllers\Api\V1\Public\TaxonomyController;
use App\Http\Controllers\Api\V1\Public\WebsiteSettingController as PublicWebsiteSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('public')->middleware(['throttle:public-api', 'locale', 'feature:pages'])->group(function (): void {
        Route::get('pages/{slug}', [PublicPageController::class, 'show'])->name('api.v1.public.pages.show');
    });
    Route::get('public/menus/{location}', [PublicMenuController::class, 'show'])
        ->middleware(['throttle:public-api', 'locale', 'feature:menus'])
        ->name('api.v1.public.menus.show');
    Route::middleware(['throttle:public-api', 'locale', 'feature:posts'])->prefix('public/posts')->group(function (): void {
        Route::get('/', [PublicPostController::class, 'index'])->name('api.v1.public.posts.index');
        Route::get('{slug}', [PublicPostController::class, 'show'])->name('api.v1.public.posts.show');
    });
    Route::middleware(['throttle:public-api', 'locale', 'feature:services'])->prefix('public/services')->group(function (): void {
        Route::get('/', [PublicServiceController::class, 'index'])->name('api.v1.public.services.index');
        Route::get('{slug}', [PublicServiceController::class, 'show'])->name('api.v1.public.services.show');
    });
    Route::middleware(['throttle:public-api', 'locale', 'feature:portfolio'])->prefix('public/portfolio')->group(function (): void {
        Route::get('/', [PublicPortfolioController::class, 'index'])->name('api.v1.public.portfolio.index');
        Route::get('{slug}', [PublicPortfolioController::class, 'show'])->name('api.v1.public.portfolio.show');
    });
    Route::post('public/contact-messages', [PublicContactMessageController::class, 'store'])
        ->middleware(['throttle:contact-form', 'locale', 'feature:contact-messages'])
        ->name('api.v1.public.contact-messages.store');
    Route::get('public/categories', [TaxonomyController::class, 'categories'])->middleware(['throttle:public-api', 'locale']);
    Route::get('public/tags', [TaxonomyController::class, 'tags'])->middleware(['throttle:public-api', 'locale']);
    Route::get('public/website-settings', [PublicWebsiteSettingController::class, 'index'])
        ->middleware(['throttle:public-api', 'locale', 'feature:website-settings'])
        ->name('api.v1.public.website-settings.index');
    Route::get('public/integrations', [PublicIntegrationController::class, 'index'])
        ->middleware(['throttle:public-api', 'locale', 'feature:integrations'])
        ->name('api.v1.public.integrations.index');

    Route::prefix('admin')->group(function (): void {
        Route::post('tokens', [AuthTokenController::class, 'store'])->middleware('throttle:login');

        Route::middleware(['auth:sanctum', 'throttle:admin-api'])->group(function (): void {
            Route::get('dashboard', DashboardController::class)
                ->middleware('feature:dashboard')
                ->name('api.v1.admin.dashboard');
            Route::get('search', GlobalSearchController::class)
                ->middleware('feature:global-search')
                ->name('api.v1.admin.search');
            Route::get('system/settings', [SystemController::class, 'settings'])
                ->middleware('feature:system')
                ->name('api.v1.admin.system.settings');
            Route::get('system/health', [SystemController::class, 'health'])
                ->middleware('feature:system')
                ->name('api.v1.admin.system.health');
            Route::delete('tokens/current', [AuthTokenController::class, 'destroy']);
            Route::get('profile', [ProfileController::class, 'show']);
            Route::put('profile', [ProfileController::class, 'update']);
            Route::put('profile/password', [ProfileController::class, 'changePassword']);
            Route::post('users/{user}/restore', [UserController::class, 'restore'])->withTrashed();
            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
            Route::get('permissions', [PermissionController::class, 'index']);
            Route::get('feature-flags', [FeatureFlagController::class, 'index']);
            Route::put('feature-flags/{feature_flag}', [FeatureFlagController::class, 'update']);
            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::post('media/{media}/restore', [MediaController::class, 'restore'])
                ->middleware('feature:media')
                ->withTrashed();
            Route::apiResource('media', MediaController::class)
                ->parameters(['media' => 'media'])
                ->middleware('feature:media');
            Route::get('pages/{page}/seo', [PageSeoController::class, 'show'])->middleware('feature:seo');
            Route::put('pages/{page}/seo', [PageSeoController::class, 'update'])->middleware('feature:seo');
            Route::get('content-blocks/schema', ContentBlockSchemaController::class);
            Route::post('pages/{page}/restore', [AdminPageController::class, 'restore'])->middleware('feature:pages')->withTrashed();
            Route::apiResource('pages', AdminPageController::class)->middleware('feature:pages');
            Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->middleware('feature:menus');
            Route::put('menus/{menu}/items/{menu_item}', [MenuItemController::class, 'update'])->middleware('feature:menus')->scopeBindings();
            Route::delete('menus/{menu}/items/{menu_item}', [MenuItemController::class, 'destroy'])->middleware('feature:menus')->scopeBindings();
            Route::apiResource('menus', AdminMenuController::class)->middleware('feature:menus');
            Route::post('posts/{post}/restore', [AdminPostController::class, 'restore'])->middleware('feature:posts')->withTrashed();
            Route::apiResource('posts', AdminPostController::class)->middleware('feature:posts');
            Route::get('posts/{post}/seo', [PostSeoController::class, 'show'])->middleware(['feature:posts', 'feature:seo']);
            Route::put('posts/{post}/seo', [PostSeoController::class, 'update'])->middleware(['feature:posts', 'feature:seo']);
            Route::post('services/{service}/restore', [AdminServiceController::class, 'restore'])->middleware('feature:services')->withTrashed();
            Route::apiResource('services', AdminServiceController::class)->middleware('feature:services');
            Route::get('services/{service}/seo', [ServiceSeoController::class, 'show'])->middleware(['feature:services', 'feature:seo']);
            Route::put('services/{service}/seo', [ServiceSeoController::class, 'update'])->middleware(['feature:services', 'feature:seo']);
            Route::post('portfolio/{portfolio}/restore', [AdminPortfolioController::class, 'restore'])->middleware('feature:portfolio')->withTrashed();
            Route::apiResource('portfolio', AdminPortfolioController::class)->parameters(['portfolio' => 'portfolio'])->middleware('feature:portfolio');
            Route::get('portfolio/{portfolio}/seo', [PortfolioSeoController::class, 'show'])->middleware(['feature:portfolio', 'feature:seo']);
            Route::put('portfolio/{portfolio}/seo', [PortfolioSeoController::class, 'update'])->middleware(['feature:portfolio', 'feature:seo']);
            Route::post('contact-messages/{contact_message}/restore', [AdminContactMessageController::class, 'restore'])->middleware('feature:contact-messages')->withTrashed();
            Route::apiResource('contact-messages', AdminContactMessageController::class)
                ->only(['index', 'show', 'update', 'destroy'])
                ->middleware('feature:contact-messages');
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('tags', TagController::class);
            Route::get('website-settings', [AdminWebsiteSettingController::class, 'index'])
                ->middleware('feature:website-settings')
                ->name('api.v1.admin.website-settings.index');
            Route::put('website-settings/{group}', [AdminWebsiteSettingController::class, 'update'])
                ->whereIn('group', WebsiteSettingGroup::values())
                ->middleware('feature:website-settings')
                ->name('api.v1.admin.website-settings.update');
            Route::get('themes', [ThemeController::class, 'index'])
                ->middleware('feature:website-settings')
                ->name('api.v1.admin.themes.index');
            Route::get('integrations', [AdminIntegrationController::class, 'index'])
                ->middleware('feature:integrations')
                ->name('api.v1.admin.integrations.index');
            Route::put('integrations/{provider}', [AdminIntegrationController::class, 'update'])
                ->whereIn('provider', IntegrationProvider::values())
                ->middleware('feature:integrations')
                ->name('api.v1.admin.integrations.update');
            Route::get('email-settings', [EmailSettingController::class, 'show'])
                ->middleware('feature:email')
                ->name('api.v1.admin.email-settings.show');
            Route::put('email-settings', [EmailSettingController::class, 'update'])
                ->middleware('feature:email')
                ->name('api.v1.admin.email-settings.update');
            Route::post('email-settings/test', [EmailSettingController::class, 'test'])
                ->middleware('feature:email')
                ->name('api.v1.admin.email-settings.test');
        });
    });
});
