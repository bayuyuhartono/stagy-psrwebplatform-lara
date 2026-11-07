<?php

namespace App\Rules;

use App\Features\Integrations\Services\RecaptchaVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidRecaptcha implements ValidationRule
{
    public function __construct(
        private RecaptchaVerifier $verifier,
        private ?string $ip = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->verifier->verify(is_string($value) ? $value : null, $this->ip)) {
            $fail('The captcha verification failed. Please try again.');
        }
    }
}
