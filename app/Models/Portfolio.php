<?php

namespace App\Models;

use App\Features\ContentBlocks\Concerns\NormalizesRichTextBlocks;
use App\Features\Portfolios\Enums\PortfolioStatus;
use Database\Factories\PortfolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['author_id', 'cover_image_id', 'title', 'slug', 'excerpt', 'client', 'project_date', 'external_url', 'status', 'is_featured', 'blocks', 'position', 'published_at'])]
class Portfolio extends Model
{
    /** @use HasFactory<PortfolioFactory> */
    use HasFactory, HasUuids, NormalizesRichTextBlocks, SoftDeletes;

    protected $attributes = ['status' => 'draft', 'is_featured' => false, 'blocks' => '[]', 'position' => 0];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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
        return ['status' => PortfolioStatus::class, 'is_featured' => 'boolean', 'blocks' => 'array', 'position' => 'integer', 'project_date' => 'date', 'published_at' => 'datetime'];
    }
}
