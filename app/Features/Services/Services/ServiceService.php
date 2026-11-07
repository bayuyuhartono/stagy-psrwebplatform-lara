<?php

namespace App\Features\Services\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Services\Enums\ServiceStatus;
use App\Features\Services\Repositories\ServiceRepository;
use App\Models\Service;

class ServiceService
{
    public function __construct(private ServiceRepository $services, private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Service
    {
        $service = $this->services->create($this->normalizePublication($attributes));
        $this->auditLogger->record('service.created', $service, newValues: $service->getAttributes());

        return $service->load('author:id,name');
    }

    /** @param array<string, mixed> $attributes */
    public function update(Service $service, array $attributes): Service
    {
        $oldValues = $service->getAttributes();
        $service = $this->services->update($service, $this->normalizePublication($attributes, $service));
        $this->auditLogger->record('service.updated', $service, $oldValues, $service->getAttributes());

        return $service->load('author:id,name');
    }

    public function delete(Service $service): void
    {
        $this->auditLogger->record('service.deleted', $service, oldValues: $service->getAttributes());
        $service->delete();
    }

    public function restore(Service $service): Service
    {
        $service->restore();
        $this->auditLogger->record('service.restored', $service, newValues: $service->getAttributes());

        return $service->load('author:id,name');
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizePublication(array $attributes, ?Service $service = null): array
    {
        $status = $attributes['status'] ?? $service?->status?->value ?? ServiceStatus::Draft->value;
        if ($status === ServiceStatus::Published->value && empty($attributes['published_at']) && $service?->published_at === null) {
            $attributes['published_at'] = now();
        }
        if ($status === ServiceStatus::Draft->value) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }
}
