<?php

namespace Database\Seeders;

use App\Features\Menus\Enums\MenuItemTarget;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed the header and footer navigation.
     *
     * Without a menu at the `header` location the public layout silently falls back
     * to hardcoded `#services` / `#work` / `#about` anchors, which only resolve on
     * the homepage. Runs after PageSeeder so page-typed items can be linked.
     */
    public function run(): void
    {
        $this->seedMenu('header', 'Header', [
            ['label' => 'Services', 'url' => '/services'],
            ['label' => 'Portfolio', 'url' => '/portfolio'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => 'News', 'url' => '/news'],
            ['label' => 'About', 'slug' => 'about'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]);

        $this->seedMenu('footer', 'Footer', [
            ['label' => 'About', 'slug' => 'about'],
            ['label' => 'Contact', 'url' => '/contact'],
            ['label' => 'Privacy Policy', 'slug' => 'privacy-policy'],
            ['label' => 'Terms & Conditions', 'slug' => 'terms-and-conditions'],
        ]);
    }

    /**
     * @param  list<array{label: string, url?: string, slug?: string}>  $items
     */
    private function seedMenu(string $location, string $name, array $items): void
    {
        $menu = Menu::query()->firstOrCreate(
            ['location' => $location],
            ['name' => $name, 'is_active' => true],
        );

        foreach ($items as $position => $item) {
            $page = isset($item['slug'])
                ? Page::query()->where('slug', $item['slug'])->first()
                : null;

            if (isset($item['slug']) && $page === null) {
                continue;
            }

            MenuItem::query()->firstOrCreate(
                ['menu_id' => $menu->getKey(), 'label' => $item['label']],
                [
                    'page_id' => $page?->getKey(),
                    'type' => $page ? MenuItemType::Page : MenuItemType::Custom,
                    'url' => $page ? null : $item['url'],
                    'target' => MenuItemTarget::Self,
                    'position' => $position,
                ],
            );
        }
    }
}
