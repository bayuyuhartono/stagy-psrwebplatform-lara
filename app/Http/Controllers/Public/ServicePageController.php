<?php

namespace App\Http\Controllers\Public;

use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Services\Repositories\ServiceRepository;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ServicePageController extends Controller
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private MenuRepository $menus,
    ) {}

    public function index(ServiceRepository $services): View
    {
        return view('public.services.index', [
            ...$this->layoutData(),
            'services' => $services->paginatePublished([], 12),
        ]);
    }

    public function show(string $slug, ServiceRepository $services): View
    {
        return view('public.services.show', [
            ...$this->layoutData(),
            'service' => $services->findPublishedBySlug($slug),
        ]);
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'settings' => $this->websiteSettings->public()->mapWithKeys(fn ($setting): array => [$setting->group->value => $setting->settings]),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
        ];
    }
}
