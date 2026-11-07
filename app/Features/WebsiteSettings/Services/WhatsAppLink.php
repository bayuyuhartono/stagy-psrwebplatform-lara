<?php

namespace App\Features\WebsiteSettings\Services;

class WhatsAppLink
{
    /**
     * A wa.me chat link for a phone number as an editor typed it
     * ("0812-3456-7890", "+62 812 3456 7890"). Local Indonesian numbers
     * starting with 0 are rewritten to the 62 country code.
     */
    public function url(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return "https://wa.me/{$digits}";
    }
}
