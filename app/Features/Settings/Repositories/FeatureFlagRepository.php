<?php

namespace App\Features\Settings\Repositories;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Collection;

class FeatureFlagRepository
{
    /** @return Collection<int, FeatureFlag> */
    public function all(): Collection
    {
        return FeatureFlag::query()->orderBy('name')->get();
    }

    /**
     * Names of the switched-on features, read once per instance.
     *
     * @return list<string>
     */
    public function enabledNames(): array
    {
        return once(fn (): array => FeatureFlag::query()->where('enabled', true)->pluck('name')->all());
    }

    public function isEnabled(string $feature): bool
    {
        return in_array($feature, $this->enabledNames(), true);
    }
}
