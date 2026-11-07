<?php

namespace App\Features\Services\Actions;

use App\Features\Services\Services\ServiceService;
use App\Models\Service;

class DeleteServiceAction
{
    public function __construct(private ServiceService $service) {}

    public function handle(Service $model): void
    {
        $this->service->delete($model);
    }
}
