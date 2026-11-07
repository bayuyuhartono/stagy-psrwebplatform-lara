<?php

namespace App\Models;

use App\Features\Localization\Concerns\HasTranslations;
use Database\Factories\PricingAddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An optional extra any plan can add, priced per unit ("/ tamu", "/ unit"). Prices are whole rupiah.
 */
#[Fillable(['name', 'price', 'unit', 'position'])]
class PricingAddon extends Model
{
    /** @use HasFactory<PricingAddonFactory> */
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    protected $attributes = ['position' => 0];

    protected function casts(): array
    {
        return ['price' => 'integer', 'position' => 'integer'];
    }
}
