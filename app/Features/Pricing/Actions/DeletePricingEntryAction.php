<?php

namespace App\Features\Pricing\Actions;

use App\Features\Pricing\Services\PricingService;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;

class DeletePricingEntryAction
{
    public function __construct(private PricingService $service) {}

    public function handle(PricingPlan|PricingComparisonRow|PricingAddon $entry): void
    {
        $this->service->delete($entry);
    }
}
