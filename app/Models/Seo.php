<?php

namespace App\Models;

use Database\Factories\SeoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'robots_index', 'robots_follow', 'og_title', 'og_description', 'og_image_id', 'twitter_card', 'structured_data', 'score'])]
class Seo extends Model
{
    /** @use HasFactory<SeoFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $attributes = [
        'robots_index' => true,
        'robots_follow' => true,
        'twitter_card' => 'summary_large_image',
        'score' => 0,
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'structured_data' => 'array',
            'score' => 'integer',
        ];
    }
}
