<?php

namespace App\Features\Pricing\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Localization\Services\TranslationLinker;
use App\Features\Pricing\Repositories\PricingRepository;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;

/**
 * Writes plans, comparison rows and add-ons. They share one lifecycle, so one
 * service serves all three (as TaxonomyService does for categories and tags).
 */
class PricingService
{
    public function __construct(
        private PricingRepository $pricing,
        private AuditLogger $auditLogger,
        private TranslationLinker $translations,
    ) {}

    /**
     * @param  class-string<PricingPlan|PricingComparisonRow|PricingAddon>  $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $modelClass, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        $entry = $this->pricing->create($modelClass, $this->translations->link($modelClass, $attributes));
        $this->auditLogger->record($this->event($entry, 'created'), $entry, newValues: $entry->getAttributes());

        return $entry;
    }

    /** @param array<string, mixed> $attributes */
    public function update(PricingPlan|PricingComparisonRow|PricingAddon $entry, array $attributes): PricingPlan|PricingComparisonRow|PricingAddon
    {
        $oldValues = $entry->getAttributes();
        $entry = $this->pricing->update($entry, $attributes);
        $this->auditLogger->record($this->event($entry, 'updated'), $entry, $oldValues, $entry->getAttributes());

        return $entry;
    }

    public function delete(PricingPlan|PricingComparisonRow|PricingAddon $entry): void
    {
        $this->auditLogger->record($this->event($entry, 'deleted'), $entry, oldValues: $entry->getAttributes());
        $entry->delete();
    }

    public function restore(PricingPlan|PricingComparisonRow|PricingAddon $entry): PricingPlan|PricingComparisonRow|PricingAddon
    {
        $entry->restore();
        $this->auditLogger->record($this->event($entry, 'restored'), $entry, newValues: $entry->getAttributes());

        return $entry;
    }

    private function event(PricingPlan|PricingComparisonRow|PricingAddon $entry, string $action): string
    {
        $subject = match (true) {
            $entry instanceof PricingPlan => 'pricing-plan',
            $entry instanceof PricingComparisonRow => 'pricing-comparison-row',
            $entry instanceof PricingAddon => 'pricing-addon',
        };

        return "{$subject}.{$action}";
    }
}
