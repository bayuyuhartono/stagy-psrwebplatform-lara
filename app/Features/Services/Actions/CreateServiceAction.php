<?php

namespace App\Features\Services\Actions;

use App\Features\Services\Services\ServiceService;
use App\Models\Service;

class CreateServiceAction
{
    public function __construct(private ServiceService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(array $attributes): Service
    {
        return $this->service->create($attributes);
    }
}
