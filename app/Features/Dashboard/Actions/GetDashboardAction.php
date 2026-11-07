<?php

namespace App\Features\Dashboard\Actions;

use App\Features\Dashboard\Services\DashboardService;

class GetDashboardAction
{
    public function __construct(private DashboardService $dashboardService) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        return $this->dashboardService->get();
    }
}
