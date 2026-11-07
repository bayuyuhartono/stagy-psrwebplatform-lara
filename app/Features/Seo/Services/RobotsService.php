<?php

namespace App\Features\Seo\Services;

class RobotsService
{
    public function content(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);
    }
}
