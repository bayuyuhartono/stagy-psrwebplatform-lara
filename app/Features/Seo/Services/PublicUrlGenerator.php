<?php

namespace App\Features\Seo\Services;

use App\Features\Localization\Services\LocaleRegistry;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class PublicUrlGenerator
{
    public function for(Model $content): string
    {
        $path = match (true) {
            $content instanceof Page => $content->slug === 'home' ? '/' : '/'.$content->slug,
            $content instanceof Post => '/'.$content->type->value.'/'.$content->slug,
            $content instanceof Service => '/services/'.$content->slug,
            $content instanceof Portfolio => '/portfolio/'.$content->slug,
            default => '/',
        };
        $locale = $content->getAttribute('locale');

        return url(is_string($locale) ? (new LocaleRegistry)->localizePath($path, $locale) : $path);
    }

    public function canonicalFor(Model $content): string
    {
        if ($content->relationLoaded('seo') && filled($content->seo?->canonical_url)) {
            return $content->seo->canonical_url;
        }

        return $this->for($content);
    }
}
