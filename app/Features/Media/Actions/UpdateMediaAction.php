<?php

namespace App\Features\Media\Actions;

use App\Features\Media\Services\MediaService;
use App\Models\Media;

class UpdateMediaAction
{
    public function __construct(private MediaService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Media $media, array $attributes): Media
    {
        return $this->service->update($media, $attributes);
    }
}
