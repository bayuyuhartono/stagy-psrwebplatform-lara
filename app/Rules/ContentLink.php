<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link an editor may put on a content-block button or card:
 * an http(s) URL, a site path ("/harga"), an in-page anchor ("#venue"),
 * a mailto:/tel: link, or the keyword "whatsapp" for the site's WhatsApp chat.
 *
 * Registered as the "content_link" string rule so block schemas in config stay cacheable.
 */
class ContentLink implements ValidationRule
{
    public const WHATSAPP = 'whatsapp';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('The :attribute must be a URL, a path starting with "/", an "#anchor", a mailto:/tel: link, or "whatsapp".');
        }
    }

    public static function isValid(string $value): bool
    {
        if ($value === self::WHATSAPP) {
            return true;
        }

        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return true;
        }

        if (preg_match('/^#[A-Za-z][\w-]*$/', $value) === 1) {
            return true;
        }

        if (preg_match('/^mailto:[^\s@]+@[^\s@]+$/', $value) === 1 || preg_match('/^tel:\+?[\d\s().-]{4,}$/', $value) === 1) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
