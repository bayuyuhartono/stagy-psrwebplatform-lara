<?php

namespace App\Features\Menus\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Menus\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;

class MenuItemService
{
    public function __construct(private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function create(Menu $menu, array $attributes): MenuItem
    {
        $item = $menu->items()->create($this->normalize($attributes));
        $this->auditLogger->record('menu-item.created', $item, newValues: $item->getAttributes());

        return $item->refresh()->load('page');
    }

    /** @param array<string, mixed> $attributes */
    public function update(MenuItem $item, array $attributes): MenuItem
    {
        $oldValues = $item->getAttributes();
        $item->update($this->normalize($attributes));
        $item = $item->refresh()->load('page');
        $this->auditLogger->record('menu-item.updated', $item, $oldValues, $item->getAttributes());

        return $item;
    }

    public function delete(MenuItem $item): void
    {
        $this->auditLogger->record('menu-item.deleted', $item, oldValues: $item->getAttributes());
        $item->delete();
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalize(array $attributes): array
    {
        if (($attributes['type'] ?? null) === MenuItemType::Page->value) {
            $attributes['url'] = null;
        } elseif (($attributes['type'] ?? null) === MenuItemType::Custom->value) {
            $attributes['page_id'] = null;
        }

        return $attributes;
    }
}
