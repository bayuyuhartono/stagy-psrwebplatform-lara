<?php

namespace App\Http\Controllers\Public;

use App\Features\ContentBlocks\Services\BlockMediaResolver;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Pages\Repositories\PageRepository;
use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private PublicWebsiteSettings $publicSettings,
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
            'settings' => $this->publicSettings->forCurrentLocale(),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
        ];
    }
}
