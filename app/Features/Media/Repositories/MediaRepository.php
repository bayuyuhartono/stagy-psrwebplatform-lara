<?php

namespace App\Features\Media\Repositories;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MediaRepository
{
    public function paginate(?string $folder = null, ?string $search = null, int $perPage = 24, bool $trashed = false): LengthAwarePaginator
    {
        return Media::query()
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->with('uploader')
            ->when($folder, fn ($query, string $folder) => $query->where('folder', $folder))
            ->when($search, fn ($query, string $search) => $query->where('original_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByChecksum(string $disk, string $checksum): ?Media
    {
        return Media::query()->withTrashed()->where('disk', $disk)->where('checksum', $checksum)->first();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Media
    {
        return Media::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Media $media, array $attributes): Media
    {
        $media->update($attributes);

        return $media->refresh();
    }
}
