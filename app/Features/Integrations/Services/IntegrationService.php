<?php

namespace App\Features\Integrations\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Integrations\Enums\IntegrationProvider;
use App\Features\Integrations\Repositories\IntegrationRepository;
use App\Models\Integration;
use Illuminate\Support\Arr;

class IntegrationService
{
    public function __construct(
        private IntegrationRepository $integrations,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function update(IntegrationProvider $provider, array $attributes): Integration
    {
        $integration = $this->integrations->findOrCreate($provider);
        $oldValues = $this->auditValues($integration);
        $publicConfig = Arr::only($attributes, $provider->publicKeys());
        $secretConfig = $integration->secret_config ?? [];

        foreach ($provider->secretKeys() as $key) {
            if (array_key_exists($key, $attributes)) {
                $secretConfig[$key] = $attributes[$key];
            }
        }

        $integration = $this->integrations->update($integration, [
            'enabled' => $attributes['enabled'],
            'public_config' => $publicConfig,
            'secret_config' => $secretConfig === [] ? null : $secretConfig,
        ]);
        $this->auditLogger->record('integration.updated', $integration, $oldValues, $this->auditValues($integration));

        return $integration;
    }

    /** @return array<string, mixed> */
    private function auditValues(Integration $integration): array
    {
        return [
            'provider' => $integration->provider->value,
            'enabled' => $integration->enabled,
            'public_config' => $integration->public_config,
            'configured_secrets' => array_keys(array_filter($integration->secret_config ?? [])),
        ];
    }
}
