<?php

namespace App\Features\Services\Actions;

use App\Features\Services\Services\ServiceService;
use App\Models\Service;

class RestoreServiceAction
{
    public function __construct(private ServiceService $service) {}

    public function handle(Service $service): Service
    {
        return $this->service->restore($service);
    }
}
