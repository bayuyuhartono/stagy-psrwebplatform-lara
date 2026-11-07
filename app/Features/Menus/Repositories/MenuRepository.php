<?php

namespace App\Features\Menus\Repositories;

use App\Models\Menu;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MenuRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Menu::query()->with('rootItems.childrenRecursive.page')->latest()->paginate($perPage);
    }

    public function loadTree(Menu $menu): Menu
    {
        return $menu->load(['rootItems.childrenRecursive.page', 'translations']);
    }

    public function findActiveByLocation(string $location): Menu
    {
        return Menu::query()->forCurrentLocale()->where('location', $location)->where('is_active', true)
            ->with('rootItems.childrenRecursive.page')->firstOrFail();
    }

    public function findActiveByLocationOrNull(string $location): ?Menu
    {
        return Menu::query()->forCurrentLocale()->where('location', $location)->where('is_active', true)
            ->with('rootItems.childrenRecursive.page')->first();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Menu
    {
        return Menu::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Menu $menu, array $attributes): Menu
    {
        $menu->update($attributes);

        return $menu->refresh();
    }
}
