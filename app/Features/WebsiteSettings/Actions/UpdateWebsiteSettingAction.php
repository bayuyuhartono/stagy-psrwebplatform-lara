<?php

namespace App\Features\WebsiteSettings\Actions;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Features\WebsiteSettings\Services\WebsiteSettingService;
use App\Models\WebsiteSetting;

class UpdateWebsiteSettingAction
{
    public function __construct(private WebsiteSettingService $websiteSettingService) {}

    /** @param array<string, mixed> $settings */
    public function handle(WebsiteSettingGroup $group, array $settings): WebsiteSetting
    {
        return $this->websiteSettingService->update($group, $settings);
    }
}
