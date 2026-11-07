<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafeMenuUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL or absolute path.');

            return;
        }

        $isSafePath = str_starts_with($value, '/') && ! str_starts_with($value, '//');
        $scheme = parse_url($value, PHP_URL_SCHEME);
        $isSafeUrl = filter_var($value, FILTER_VALIDATE_URL) !== false && in_array($scheme, ['http', 'https'], true);

        if (! $isSafePath && ! $isSafeUrl) {
            $fail('The :attribute must be an HTTP(S) URL or an absolute path.');
        }
    }
}
