<?php

namespace App\Features\Pricing\Actions;

use App\Features\Pricing\Services\PricingService;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;

class UpdatePricingEntryAction
{
    public function __construct(private PricingService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(PricingPlan|PricingComparisonRow|PricingAddon $entry, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        return $this->service->update($entry, $attributes);
    }
}
