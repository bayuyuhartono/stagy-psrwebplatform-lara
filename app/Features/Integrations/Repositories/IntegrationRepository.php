<?php

namespace App\Features\Integrations\Repositories;

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Collection;

class IntegrationRepository
{
    /** @return Collection<int, Integration> */
    public function all(): Collection
    {
        return Integration::query()->orderBy('provider')->get();
    }

    /** @return Collection<int, Integration> */
    public function enabled(): Collection
    {
        return Integration::query()->where('enabled', true)->orderBy('provider')->get();
    }

    public function findOrCreate(IntegrationProvider $provider): Integration
    {
        return Integration::query()->firstOrCreate(
            ['provider' => $provider],
            ['enabled' => false, 'public_config' => $provider->defaultPublicConfig()],
        );
    }

    /** @param array<string, mixed> $attributes */
    public function update(Integration $integration, array $attributes): Integration
    {
        $integration->update($attributes);

        return $integration->refresh();
    }
}
