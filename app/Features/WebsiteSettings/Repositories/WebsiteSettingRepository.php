<?php

namespace App\Features\WebsiteSettings\Repositories;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\WebsiteSetting;
use Illuminate\Database\Eloquent\Collection;

class WebsiteSettingRepository
{
    /** @return Collection<int, WebsiteSetting> */
    public function all(): Collection
    {
        return WebsiteSetting::query()->orderBy('group')->get();
    }

    /** @return Collection<int, WebsiteSetting> */
    public function public(): Collection
    {
        return WebsiteSetting::query()->where('is_public', true)->orderBy('group')->get();
    }

    public function findOrCreate(WebsiteSettingGroup $group): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate(
            ['group' => $group],
            ['settings' => $group->defaults(), 'is_public' => true],
        );
    }

    /** @param array<string, mixed> $settings */
    public function update(WebsiteSetting $websiteSetting, array $settings): WebsiteSetting
    {
        $websiteSetting->update(['settings' => $settings]);

        return $websiteSetting->refresh();
    }
}
