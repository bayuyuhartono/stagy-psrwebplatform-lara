<?php

namespace Database\Seeders;

use App\Features\Seo\Services\SitemapService;
use Illuminate\Database\Seeder;

/**
 * Initial content for the Stagy landing site (theme "stagy"), in Indonesian.
 * English versions are created from the CMS with "Add translation".
 *
 * Every step skips or fills around what already exists, so it is safe to run again.
 */
class StagySiteSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StagySettingsSeeder::class,
            StagyPricingSeeder::class,
            // Menus link to pages, so pages come first.
            StagyPageSeeder::class,
            StagyMenuSeeder::class,
            StagyBlogSeeder::class,
        ]);

        // Seeders run without model events, so the sitemap observer never fires.
        app(SitemapService::class)->invalidate();
    }
}
