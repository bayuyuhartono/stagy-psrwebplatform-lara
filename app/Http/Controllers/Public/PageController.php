<?php

namespace App\Http\Controllers\Public;

use App\Features\ContentBlocks\Services\BlockMediaResolver;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Pages\Repositories\PageRepository;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private MenuRepository $menus,
    ) {}

    public function show(string $slug, PageRepository $pages, BlockMediaResolver $blockMedia): View
    {
        $page = $pages->findPublishedBySlug($slug);

        return view('public.pages.show', [
            ...$this->layoutData(),
            'page' => $page,
            'blockMedia' => $blockMedia->resolve($page->blocks),
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
