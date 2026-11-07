<?php

namespace App\Features\Services\Repositories;

use App\Features\Services\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ServiceRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)->with('author:id,name')->orderBy('position')->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $filters */
    public function paginatePublished(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtered($filters)->where('status', ServiceStatus::Published)->where('published_at', '<=', now())
            ->with('seo.ogImage')->orderBy('position')->latest('published_at')->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): Service
    {
        return Service::query()->where('slug', $slug)->where('status', ServiceStatus::Published)->where('published_at', '<=', now())
            ->with('seo.ogImage')->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Service
    {
        return Service::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Service $service, array $attributes): Service
    {
        $service->update($attributes);

        return $service->refresh();
    }

    /** @param array<string, mixed> $filters */
    private function filtered(array $filters): Builder
    {
        return Service::query()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($searchQuery) => $searchQuery->where('title', 'like', "%{$search}%")->orWhere('excerpt', 'like', "%{$search}%")));
    }
}
