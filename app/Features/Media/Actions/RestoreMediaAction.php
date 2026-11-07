<?php

namespace App\Features\Media\Actions;

use App\Features\Media\Services\MediaService;
use App\Models\Media;

class RestoreMediaAction
{
    public function __construct(private MediaService $service) {}

    public function handle(Media $media): Media
    {
        return $this->service->restore($media);
    }
}
