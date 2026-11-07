<?php

namespace App\Features\Seo\Services;

use Illuminate\Support\Str;

class SeoScoreCalculator
{
    /** @param array<string, mixed> $attributes */
    public function calculate(array $attributes): int
    {
        $score = 0;
        $title = (string) ($attributes['meta_title'] ?? '');
        $description = (string) ($attributes['meta_description'] ?? '');
        $keyword = (string) ($attributes['focus_keyword'] ?? '');

        $score += Str::length($title) >= 30 && Str::length($title) <= 60 ? 20 : 0;
        $score += Str::length($description) >= 120 && Str::length($description) <= 160 ? 20 : 0;
        $score += $keyword !== '' ? 10 : 0;
        $score += $keyword !== '' && Str::contains(Str::lower($title), Str::lower($keyword)) ? 10 : 0;
        $score += filled($attributes['canonical_url'] ?? null) ? 10 : 0;
        $score += filled($attributes['og_title'] ?? null) ? 5 : 0;
        $score += filled($attributes['og_description'] ?? null) ? 5 : 0;
        $score += filled($attributes['og_image_id'] ?? null) ? 5 : 0;
        $score += filled($attributes['structured_data'] ?? null) ? 10 : 0;
        $score += ($attributes['robots_index'] ?? false) ? 5 : 0;

        return $score;
    }
}
