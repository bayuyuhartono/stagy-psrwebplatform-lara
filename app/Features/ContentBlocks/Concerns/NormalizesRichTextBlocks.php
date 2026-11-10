<?php

namespace App\Features\ContentBlocks\Concerns;

use App\Features\ContentBlocks\Services\RichTextContent;

/**
 * Sanitizes rich_text block HTML whenever a model's blocks are saved, so the
 * CMS, the API, seeders and factories all store the same safe HTML.
 */
trait NormalizesRichTextBlocks
{
    public static function bootNormalizesRichTextBlocks(): void
    {
        static::saving(function (self $model): void {
            if ($model->isDirty('blocks') && is_array($model->blocks)) {
                $model->blocks = app(RichTextContent::class)->normalizeBlocks($model->blocks);
            }
        });
    }
}
