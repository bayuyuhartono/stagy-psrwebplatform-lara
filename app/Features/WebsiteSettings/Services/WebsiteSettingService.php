<?php

namespace App\Features\WebsiteSettings\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Models\WebsiteSetting;

class WebsiteSettingService
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $settings */
    public function update(WebsiteSettingGroup $group, array $settings): WebsiteSetting
    {
        $websiteSetting = $this->websiteSettings->findOrCreate($group);
        $oldValues = $websiteSetting->getAttributes();
        $websiteSetting = $this->websiteSettings->update($websiteSetting, $settings);
        $this->auditLogger->record('website_setting.updated', $websiteSetting, $oldValues, $websiteSetting->getAttributes());

        return $websiteSetting;
    }
}
