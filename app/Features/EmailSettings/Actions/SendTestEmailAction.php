<?php

namespace App\Features\EmailSettings\Actions;

use App\Features\EmailSettings\Services\EmailSettingService;

class SendTestEmailAction
{
    public function __construct(private EmailSettingService $emailSettingService) {}

    public function handle(string $recipient): void
    {
        $this->emailSettingService->sendTest($recipient);
    }
}
