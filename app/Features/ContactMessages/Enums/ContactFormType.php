<?php

namespace App\Features\ContactMessages\Enums;

enum ContactFormType: string
{
    case Contact = 'contact';
    case Demo = 'demo';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
