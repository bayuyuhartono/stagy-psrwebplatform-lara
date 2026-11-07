<?php

namespace App\Features\Pricing\Actions;

use App\Features\Pricing\Services\PricingService;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;

class CreatePricingEntryAction
{
    public function __construct(private PricingService $service) {}

    /**
     * @param  class-string<PricingPlan|PricingComparisonRow|PricingAddon>  $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function handle(string $modelClass, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        return $this->service->create($modelClass, $attributes);
    }
}
