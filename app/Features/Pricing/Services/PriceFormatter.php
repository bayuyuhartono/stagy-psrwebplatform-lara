<?php

namespace App\Features\Pricing\Services;

/**
 * Formats whole-rupiah amounts the way the pricing page reads them:
 * millions are abbreviated ("Rp8,1 juta" / "IDR 8.1 million"), smaller
 * amounts are written out with thousands separators ("Rp2.500" / "IDR 2,500").
 */
class PriceFormatter
{
    public function format(int $amount, string $locale): string
    {
        $isIndonesian = $locale === 'id';
        [$decimal, $thousands] = $isIndonesian ? [',', '.'] : ['.', ','];

        if ($amount >= 1_000_000) {
            $millions = round($amount / 1_000_000, 1);
            $number = number_format($millions, fmod($millions, 1.0) === 0.0 ? 0 : 1, $decimal, $thousands);

            return $isIndonesian ? "Rp{$number} juta" : "IDR {$number} million";
        }

        $number = number_format($amount, 0, $decimal, $thousands);

        return $isIndonesian ? "Rp{$number}" : "IDR {$number}";
    }
}
