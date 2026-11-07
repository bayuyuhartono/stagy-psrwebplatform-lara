<?php

namespace App\Features\Integrations\Services;

use App\Features\Integrations\Repositories\IntegrationRepository;
use App\Models\Integration;

/**
 * Resolves the integration configuration the public website is allowed to render.
 *
 * Only `public_config` is ever surfaced here — provider secrets live in the
 * encrypted `secret_config` and must never reach a rendered page.
 */
class PublicIntegrationResolver
{
    public function __construct(private IntegrationRepository $integrations) {}

    /**
     * Enabled providers keyed by provider value, with blank config entries removed.
     *
     * @return array<string, array<string, mixed>>
     */
    public function enabledConfigs(): array
    {
        return $this->integrations->enabled()
            ->mapWithKeys(fn (Integration $integration): array => [
                $integration->provider->value => array_filter(
                    $integration->public_config ?? [],
                    fn (mixed $value): bool => filled($value),
                ),
            ])
            ->filter(fn (array $config): bool => $config !== [])
            ->all();
    }
}
