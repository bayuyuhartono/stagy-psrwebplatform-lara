<?php

namespace Database\Seeders;

use App\Features\Seo\Services\SitemapService;
use Illuminate\Database\Seeder;

/**
 * Initial content for the Stagy landing site (theme "stagy"), in Indonesian and English.
 * The English versions are seeded afterwards and linked to them as translations.
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
            StagyEnglishSeeder::class,
        ]);

        // Seeders run without model events, so the sitemap observer never fires.
        app(SitemapService::class)->invalidate();
    }
}
