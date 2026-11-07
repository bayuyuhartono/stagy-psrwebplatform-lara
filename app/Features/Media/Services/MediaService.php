<?php

namespace App\Features\Media\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Media\Repositories\MediaRepository;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MediaService
{
    public function __construct(
        private MediaRepository $media,
        private AuditLogger $auditLogger,
        private ResponsiveImageGenerator $responsiveImages,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function upload(User $uploader, UploadedFile $file, array $attributes): Media
    {
        $disk = config('media.disk');
        $checksum = hash_file('sha256', $file->getRealPath());
        $existingMedia = $this->media->findByChecksum($disk, $checksum);

        if ($existingMedia) {
            if ($existingMedia->trashed()) {
                $existingMedia->restore();
                $this->auditLogger->record('media.restored', $existingMedia, newValues: $existingMedia->getAttributes());
            }

            return $existingMedia->load('uploader');
        }

        $folder = Arr::get($attributes, 'folder');
        $directory = $folder ? "media/{$folder}" : 'media';
        $path = $file->store($directory, $disk);

        if (! $path) {
            throw new RuntimeException('The media file could not be stored.');
        }

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $variants = [];

        try {
            $metadata = $this->imageMetadata($file);
            $variants = $this->responsiveImages->generate($disk, $path, $mimeType);

            if ($variants !== []) {
                $metadata['responsive'] = $variants;
            }

            $createdMedia = $this->media->create([
                ...$attributes,
                'uploader_id' => $uploader->getKey(),
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'extension' => $file->extension(),
                'size' => $file->getSize(),
                'checksum' => $checksum,
                'metadata' => $metadata ?: null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            $this->responsiveImages->purge($disk, $variants);

            throw $exception;
        }

        $this->auditLogger->record('media.uploaded', $createdMedia, newValues: $createdMedia->getAttributes());

        return $createdMedia->load('uploader');
    }

    /** @param array<string, mixed> $attributes */
    public function update(Media $media, array $attributes): Media
    {
        $oldValues = $media->getAttributes();
        $oldPath = $media->path;
        $newPath = $oldPath;

        if (array_key_exists('folder', $attributes) && $attributes['folder'] !== $media->folder) {
            $directory = $attributes['folder'] ? "media/{$attributes['folder']}" : 'media';
            $newPath = $directory.'/'.basename($oldPath);

            if (! Storage::disk($media->disk)->move($oldPath, $newPath)) {
                throw new RuntimeException('The media file could not be moved.');
            }

            $attributes['path'] = $newPath;
        }

        try {
            $updatedMedia = $this->media->update($media, $attributes);
        } catch (Throwable $exception) {
            if ($newPath !== $oldPath) {
                Storage::disk($media->disk)->move($newPath, $oldPath);
            }

            throw $exception;
        }

        $this->auditLogger->record('media.updated', $updatedMedia, $oldValues, $updatedMedia->getAttributes());

        return $updatedMedia->load('uploader');
    }

    public function delete(Media $media): void
    {
        $this->auditLogger->record('media.deleted', $media, oldValues: $media->getAttributes());
        $media->delete();
    }

    public function restore(Media $media): Media
    {
        $media->restore();
        $this->auditLogger->record('media.restored', $media, newValues: $media->getAttributes());

        return $media->load('uploader');
    }

    /** @return array{width: int, height: int}|array{} */
    private function imageMetadata(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return [];
        }

        $dimensions = getimagesize($file->getRealPath());

        return $dimensions ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : [];
    }
}
