<?php

namespace App\Features\Menus\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Localization\Services\TranslationLinker;
use App\Features\Menus\Repositories\MenuRepository;
use App\Models\Menu;

class MenuService
{
    public function __construct(
        private MenuRepository $menus,
        private AuditLogger $auditLogger,
        private TranslationLinker $translations,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Menu
    {
        $menu = $this->menus->create($this->translations->link(Menu::class, $attributes));
        $this->auditLogger->record('menu.created', $menu, newValues: $menu->getAttributes());

        return $menu;
    }

    /** @param array<string, mixed> $attributes */
    public function update(Menu $menu, array $attributes): Menu
    {
        $oldValues = $menu->getAttributes();
        $menu = $this->menus->update($menu, $attributes);
        $this->auditLogger->record('menu.updated', $menu, $oldValues, $menu->getAttributes());

        return $menu;
    }

    public function delete(Menu $menu): void
    {
        $this->auditLogger->record('menu.deleted', $menu, oldValues: $menu->getAttributes());
        $menu->delete();
    }
}
