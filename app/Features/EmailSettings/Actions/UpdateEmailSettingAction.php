<?php

namespace App\Features\EmailSettings\Actions;

use App\Features\EmailSettings\Services\EmailSettingService;
use App\Models\EmailSetting;

class UpdateEmailSettingAction
{
    public function __construct(private EmailSettingService $emailSettingService) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): EmailSetting
    {
        return $this->emailSettingService->update($attributes);
    }
}
