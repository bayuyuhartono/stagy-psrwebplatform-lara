<?php

namespace App\Features\ContentBlocks\Services;

use App\Models\Media;
use Illuminate\Support\Collection;

/**
 * Blocks reference media by UUID inside their JSON payload, so there is no
 * Eloquent relation to eager load. This resolves every referenced id for a set
 * of blocks in one query, keyed for O(1) lookup while rendering.
 */
class BlockMediaResolver
{
    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     * @return Collection<string, Media>
     */
    public function resolve(?array $blocks): Collection
    {
        $ids = collect($blocks ?? [])
            ->map(fn (array $block): mixed => $block['data']['media_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Media::query()->whereIn('id', $ids)->get()->keyBy('id');
    }
}
