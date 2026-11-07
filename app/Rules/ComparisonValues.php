<?php

namespace App\Rules;

use App\Features\Pricing\Repositories\PricingRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A comparison row's cells: keyed by the id of a plan in the same locale, each
 * either true (included), false (not included) or a short text.
 */
class ComparisonValues implements ValidationRule
{
    public function __construct(private string $locale) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $planIds = app(PricingRepository::class)->planIds($this->locale);

        foreach ($value as $planId => $cell) {
            if (! in_array((string) $planId, $planIds, true)) {
                $fail("The :attribute contain a plan [{$planId}] that does not exist in this language.");

                return;
            }

            if (! is_bool($cell) && ! (is_string($cell) && mb_strlen($cell) <= 60)) {
                $fail('Each :attribute entry must be true, false, or a text of at most 60 characters.');

                return;
            }
        }
    }
}
