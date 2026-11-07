<?php

namespace App\Features\Media\Actions;

use App\Features\Media\Services\MediaService;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UploadMediaAction
{
    public function __construct(private MediaService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $uploader, UploadedFile $file, array $attributes): Media
    {
        return $this->service->upload($uploader, $file, $attributes);
    }
}
