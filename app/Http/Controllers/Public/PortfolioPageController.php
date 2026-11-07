<?php

namespace App\Http\Controllers\Public;

use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Portfolios\Repositories\PortfolioRepository;
use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PortfolioPageController extends Controller
{
    public function __construct(
        private PublicWebsiteSettings $publicSettings,
        private MenuRepository $menus,
    ) {}

    public function index(PortfolioRepository $portfolio): View
    {
        return view('public.portfolio.index', [
            ...$this->layoutData(),
            'projects' => $portfolio->paginatePublished([], 10),
        ]);
    }

    public function show(string $slug, PortfolioRepository $portfolio): View
    {
        return view('public.portfolio.show', [
            ...$this->layoutData(),
            'project' => $portfolio->findPublishedBySlug($slug),
        ]);
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'settings' => $this->publicSettings->forCurrentLocale(),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
        ];
    }
}
