<?php

namespace App\Features\Integrations\Actions;

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Features\Integrations\Services\IntegrationService;
use App\Models\Integration;

class UpdateIntegrationAction
{
    public function __construct(private IntegrationService $integrationService) {}

    /** @param array<string, mixed> $attributes */
    public function handle(IntegrationProvider $provider, array $attributes): Integration
    {
        return $this->integrationService->update($provider, $attributes);
    }
}
