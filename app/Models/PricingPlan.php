<?php

namespace App\Models;

use App\Features\Localization\Concerns\HasTranslations;
use Database\Factories\PricingPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A package on the pricing page, written per locale. Prices are whole rupiah.
 */
#[Fillable(['name', 'tagline', 'price', 'extra_guest_price', 'features', 'support_summary', 'is_highlighted', 'highlight_label', 'cta_label', 'cta_url', 'position'])]
class PricingPlan extends Model
{
    /** @use HasFactory<PricingPlanFactory> */
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    protected $attributes = ['features' => '[]', 'is_highlighted' => false, 'position' => 0];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'extra_guest_price' => 'integer',
            'features' => 'array',
            'is_highlighted' => 'boolean',
            'position' => 'integer',
        ];
    }
}
