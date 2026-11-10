<?php

namespace App\Models;

use App\Features\ContentBlocks\Concerns\NormalizesRichTextBlocks;
use App\Features\Services\Enums\ServiceStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['author_id', 'title', 'slug', 'excerpt', 'icon', 'status', 'blocks', 'position', 'published_at'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasUuids, NormalizesRichTextBlocks, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'blocks' => '[]', 'position' => 0];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    protected function casts(): array
    {
        return ['status' => ServiceStatus::class, 'blocks' => 'array', 'position' => 'integer', 'published_at' => 'datetime'];
    }
}
