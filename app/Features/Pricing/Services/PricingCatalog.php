<?php

namespace App\Features\Pricing\Services;

use App\Features\Localization\Services\LocaleRegistry;
use App\Features\Pricing\Repositories\PricingRepository;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use Illuminate\Support\Collection;

/**
 * Everything the pricing blocks and the public API show for one locale.
 *
 * Comparison rows whose value is the same for every plan are lifted out of the
 * table into `included_in_all` ("Export Excel", "Undangan WhatsApp: 200 tamu");
 * rows no plan includes are dropped. Bound as a scoped singleton so several
 * pricing blocks on one page share a single load.
 */
class PricingCatalog
{
    /** @var array<string, array{plans: Collection<int, PricingPlan>, groups: list<array{title: string, rows: list<array{label: string, cells: list<bool|string>}>}>, included_in_all: list<string>, addons: Collection<int, PricingAddon>}> */
    private array $loaded = [];

    public function __construct(
        private PricingRepository $pricing,
        private LocaleRegistry $locales,
    ) {}

    /**
     * @return array{plans: Collection<int, PricingPlan>, groups: list<array{title: string, rows: list<array{label: string, cells: list<bool|string>}>}>, included_in_all: list<string>, addons: Collection<int, PricingAddon>}
     */
    public function for(?string $locale = null): array
    {
        $locale ??= $this->locales->current();

        return $this->loaded[$locale] ??= $this->build($locale);
    }

    /**
     * @return array{plans: Collection<int, PricingPlan>, groups: list<array{title: string, rows: list<array{label: string, cells: list<bool|string>}>}>, included_in_all: list<string>, addons: Collection<int, PricingAddon>}
     */
    private function build(string $locale): array
    {
        /** @var Collection<int, PricingPlan> $plans */
        $plans = $this->pricing->ordered(PricingPlan::class, $locale);
        $planIds = $plans->pluck('id')->all();
        $groups = [];
        $includedInAll = [];

        /** @var PricingComparisonRow $row */
        foreach ($this->pricing->ordered(PricingComparisonRow::class, $locale) as $row) {
            $cells = array_map(fn (string $planId): bool|string => $this->cell($row->values[$planId] ?? false), $planIds);
            $isUniform = count($planIds) > 1 && count(array_unique($cells, SORT_REGULAR)) === 1;

            if ($isUniform) {
                if ($cells[0] === true) {
                    $includedInAll[] = $row->label;
                } elseif (is_string($cells[0])) {
                    $includedInAll[] = "{$row->label}: {$cells[0]}";
                }

                continue;
            }

            $groups[$row->group_name] ??= ['title' => $row->group_name, 'rows' => []];
            $groups[$row->group_name]['rows'][] = ['label' => $row->label, 'cells' => $cells];
        }

        return [
            'plans' => $plans,
            'groups' => array_values($groups),
            'included_in_all' => $includedInAll,
            'addons' => $this->pricing->ordered(PricingAddon::class, $locale),
        ];
    }

    private function cell(mixed $value): bool|string
    {
        if (is_bool($value)) {
            return $value;
        }

        $text = trim((string) $value);

        return $text === '' ? false : $text;
    }
}
