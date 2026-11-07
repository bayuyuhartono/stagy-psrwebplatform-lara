<?php

namespace App\Features\Pricing\Repositories;

use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Collection;

class PricingRepository
{
    /**
     * Every entry of one pricing model in a locale, in display order.
     *
     * @param  class-string<PricingPlan|PricingComparisonRow|PricingAddon>  $modelClass
     * @return Collection<int, PricingPlan|PricingComparisonRow|PricingAddon>
     */
    public function ordered(string $modelClass, string $locale): Collection
    {
        return $modelClass::query()
            ->forLocale($locale)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Ids of the plans in a locale, used to validate comparison values.
     *
     * @return list<string>
     */
    public function planIds(string $locale): array
    {
        return PricingPlan::query()->forLocale($locale)->pluck('id')->all();
    }

    /**
     * @param  class-string<PricingPlan|PricingComparisonRow|PricingAddon>  $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $modelClass, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        return $modelClass::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(PricingPlan|PricingComparisonRow|PricingAddon $entry, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        $entry->update($attributes);

        return $entry->refresh();
    }
}
