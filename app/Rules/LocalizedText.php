<?php

namespace App\Rules;

use App\Features\Localization\Services\LocaleRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A setting written per language: either a plain string, or an object keyed
 * by supported locale whose values are strings (or null) up to a length.
 */
class LocalizedText implements ValidationRule
{
    public function __construct(private int $max) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $values = is_array($value) ? $value : ['' => $value];
        $supported = app(LocaleRegistry::class)->supported();

        foreach ($values as $locale => $text) {
            if (is_array($value) && ! in_array((string) $locale, $supported, true)) {
                $fail("The :attribute has a value for an unsupported language [{$locale}].");

                return;
            }

            if ($text !== null && (! is_string($text) || mb_strlen($text) > $this->max)) {
                $fail("Each :attribute value must be text of at most {$this->max} characters.");

                return;
            }
        }
    }
}
