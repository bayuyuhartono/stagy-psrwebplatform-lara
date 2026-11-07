<?php

namespace App\Http\Controllers\Public;

use App\Features\ContentBlocks\Services\BlockMediaResolver;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Pages\Repositories\PageRepository;
use App\Features\Portfolios\Repositories\PortfolioRepository;
use App\Features\Services\Repositories\ServiceRepository;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(
        PageRepository $pages,
        ServiceRepository $services,
        PortfolioRepository $portfolio,
        WebsiteSettingRepository $websiteSettings,
        MenuRepository $menus,
        BlockMediaResolver $blockMedia,
    ): View {
        $page = $pages->findPublishedBySlugOrNull('home');

        return view('public.home', [
            'page' => $page,
            'blockMedia' => $blockMedia->resolve($page?->blocks),
            'settings' => $websiteSettings->public()->mapWithKeys(fn ($setting): array => [$setting->group->value => $setting->settings]),
            'menu' => $menus->findActiveByLocationOrNull('header'),
            'services' => $services->paginatePublished([], 6)->getCollection(),
            'portfolio' => $portfolio->paginatePublished([], 4)->getCollection(),
        ]);
    }
}
