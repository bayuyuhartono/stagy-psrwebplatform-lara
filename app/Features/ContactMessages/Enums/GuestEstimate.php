<?php

namespace App\Features\ContactMessages\Enums;

/**
 * The guest-count bands offered on the demo request form.
 */
enum GuestEstimate: string
{
    case UnderFiveHundred = 'under_500';
    case FiveHundredToTwoThousand = '500_2000';
    case OverTwoThousand = 'over_2000';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(string $locale = 'id'): string
    {
        $isIndonesian = $locale === 'id';

        return match ($this) {
            self::UnderFiveHundred => $isIndonesian ? '< 500 tamu' : '< 500 guests',
            self::FiveHundredToTwoThousand => $isIndonesian ? '500–2.000 tamu' : '500–2,000 guests',
            self::OverTwoThousand => $isIndonesian ? '> 2.000 tamu' : '> 2,000 guests',
        };
    }
}
