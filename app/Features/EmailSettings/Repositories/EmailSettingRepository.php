<?php

namespace App\Features\EmailSettings\Repositories;

use App\Models\EmailSetting;

class EmailSettingRepository
{
    public function get(): EmailSetting
    {
        return EmailSetting::query()->firstOrCreate(
            ['key' => 'default'],
            ['enabled' => false, 'port' => 587, 'auto_reply_enabled' => false],
        );
    }

    /** @param array<string, mixed> $attributes */
    public function update(EmailSetting $emailSetting, array $attributes): EmailSetting
    {
        $emailSetting->update($attributes);

        return $emailSetting->refresh();
    }
}
