<?php

namespace App\Http\Controllers\Public;

use App\Features\Seo\Services\SitemapService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SitemapService $sitemap): Response
    {
        return response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
