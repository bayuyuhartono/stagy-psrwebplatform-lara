<?php

namespace App\Features\Media\Actions;

use App\Features\Media\Services\MediaService;
use App\Models\Media;

class DeleteMediaAction
{
    public function __construct(private MediaService $service) {}

    public function handle(Media $media): void
    {
        $this->service->delete($media);
    }
}
