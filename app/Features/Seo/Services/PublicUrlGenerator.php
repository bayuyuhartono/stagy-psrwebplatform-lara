<?php

namespace App\Features\Seo\Services;

use App\Features\Localization\Services\LocaleRegistry;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class PublicUrlGenerator
{
    public function for(Model $content): string
    {
        return url($this->pathFor($content));
    }

    /**
     * The site-relative path of a piece of content, including its locale prefix.
     */
    public function pathFor(Model $content): string
    {
        $path = match (true) {
            $content instanceof Page => $content->slug === 'home' ? '/' : '/'.$content->slug,
            $content instanceof Post => '/'.$content->type->value.'/'.$content->slug,
            $content instanceof Service => '/services/'.$content->slug,
            $content instanceof Portfolio => '/portfolio/'.$content->slug,
            default => '/',
        };
        $locale = $content->getAttribute('locale');

        return is_string($locale) ? (new LocaleRegistry)->localizePath($path, $locale) : $path;
    }

    /**
     * Where a menu item points: its linked page's path, or its custom URL.
     */
    public function forMenuItem(MenuItem $item): string
    {
        if ($item->type === MenuItemType::Page) {
            return $item->page !== null ? $this->pathFor($item->page) : '#';
        }

        return (string) $item->url;
    }

    public function canonicalFor(Model $content): string
    {
        if ($content->relationLoaded('seo') && filled($content->seo?->canonical_url)) {
            return $content->seo->canonical_url;
        }

        return $this->for($content);
    }
}
