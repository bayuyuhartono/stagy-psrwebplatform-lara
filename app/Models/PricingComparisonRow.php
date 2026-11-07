<?php

namespace App\Models;

use App\Features\Localization\Concerns\HasTranslations;
use Database\Factories\PricingComparisonRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One line of the plan comparison table. `values` is keyed by pricing plan id:
 * true (included), false (not included) or a short text such as "2" or "Add-on".
 */
#[Fillable(['group_name', 'label', 'values', 'position'])]
class PricingComparisonRow extends Model
{
    /** @use HasFactory<PricingComparisonRowFactory> */
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    protected $attributes = ['values' => '{}', 'position' => 0];

    protected function casts(): array
    {
        return ['values' => 'array', 'position' => 'integer'];
    }
}
