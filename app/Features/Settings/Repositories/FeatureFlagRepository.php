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
}
