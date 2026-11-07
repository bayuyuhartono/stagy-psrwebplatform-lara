<?php

namespace App\Features\Seo\Services;

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

        return url($path);
    }

    public function canonicalFor(Model $content): string
    {
        if ($content->relationLoaded('seo') && filled($content->seo?->canonical_url)) {
            return $content->seo->canonical_url;
        }

        return $this->for($content);
    }
}
