<?php

namespace App\Features\Services\Actions;

use App\Features\Services\Services\ServiceService;
use App\Models\Service;

class UpdateServiceAction
{
    public function __construct(private ServiceService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(Service $model, array $attributes): Service
    {
        return $this->service->update($model, $attributes);
    }
}
