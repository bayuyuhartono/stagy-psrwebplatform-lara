<?php

namespace Database\Seeders;

use App\Features\Menus\Enums\MenuItemTarget;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The Stagy header and footer menus in Indonesian. A menu that already exists
 * is left untouched, so editors' changes survive a re-run.
 */
class StagyMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMenu('header', 'Menu utama', [['Fitur', 'fitur'], ['Cara Kerja', 'cara-kerja'], ['Harga', 'harga'], ['FAQ', 'faq'], ['Blog', '/blog']]);
        $this->seedMenu('footer-product', 'Footer · Produk', [['Fitur', 'fitur'], ['Cara Kerja', 'cara-kerja'], ['Harga', 'harga'], ['Blog', '/blog']]);
        $this->seedMenu('footer-help', 'Footer · Bantuan', [['FAQ', 'faq'], ['Minta Demo', 'demo'], ['Syarat & Ketentuan', 'syarat-ketentuan'], ['Kebijakan Privasi', 'kebijakan-privasi']]);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items  Label and either a page slug or a path starting with "/".
     */
    private function seedMenu(string $location, string $name, array $items): void
    {
        if (Menu::query()->where('location', $location)->where('locale', 'id')->exists()) {
            return;
        }

        $menu = Menu::query()->create([...Menu::translationDefaults('id'), 'location' => $location, 'name' => $name, 'is_active' => true]);

        foreach ($items as $position => [$label, $target]) {
            $page = str_starts_with($target, '/') ? null : Page::query()->where('slug', $target)->where('locale', 'id')->first();

            MenuItem::query()->create([
                'menu_id' => $menu->getKey(),
                'label' => $label,
                'type' => $page ? MenuItemType::Page : MenuItemType::Custom,
                'page_id' => $page?->getKey(),
                'url' => $page ? null : $target,
                'target' => MenuItemTarget::Self,
                'position' => $position,
            ]);
        }
    }
}
