<?php

namespace App\Features\Seo\Services;

use Illuminate\Support\Str;

class DocumentTitle
{
    public const SEPARATOR = ' · ';

    /**
     * Append the site name to a page title ("Harga · Stagy") unless the title
     * already mentions it. An editor-written SEO meta title is used as-is and
     * never passes through here.
     */
    public static function withBrand(string $title, ?string $siteName): string
    {
        $title = trim($title);

        if (blank($siteName) || $title === '' || Str::contains($title, $siteName, ignoreCase: true)) {
            return $title;
        }

        return $title.self::SEPARATOR.$siteName;
    }
}
