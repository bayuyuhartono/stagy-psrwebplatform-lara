<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['uploader_id', 'mediable_type', 'mediable_id', 'disk', 'path', 'folder', 'original_name', 'mime_type', 'extension', 'size', 'checksum', 'alt_text', 'caption', 'metadata'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Publicly resolvable URL for the stored file on its own disk.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk($this->disk)->url($this->path))->shouldCache();
    }

    /**
     * A `srcset` value covering every derived variant plus the original, or null
     * when no variants were generated (non-image, tiny image, or GIF).
     *
     * @return Attribute<?string, never>
     */
    protected function srcset(): Attribute
    {
        return Attribute::get(function (): ?string {
            $variants = $this->metadata['responsive'] ?? [];

            if ($variants === []) {
                return null;
            }

            $sources = array_map(
                fn (array $variant): string => Storage::disk($this->disk)->url($variant['path'])." {$variant['width']}w",
                $variants,
            );

            if ($width = $this->metadata['width'] ?? null) {
                $sources[] = "{$this->url} {$width}w";
            }

            return implode(', ', $sources);
        })->shouldCache();
    }

    protected function casts(): array
    {
        return ['metadata' => 'array', 'size' => 'integer'];
    }
}
