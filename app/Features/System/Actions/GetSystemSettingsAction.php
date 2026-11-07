<?php

namespace App\Features\System\Actions;

use App\Features\System\Services\SystemService;

class GetSystemSettingsAction
{
    public function __construct(private SystemService $systemService) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        return $this->systemService->settings();
    }
}
