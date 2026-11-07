<?php

namespace App\Features\Portfolios\Actions;

use App\Features\Portfolios\Services\PortfolioService;
use App\Models\Portfolio;

class CreatePortfolioAction
{
    public function __construct(private PortfolioService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(array $attributes): Portfolio
    {
        return $this->service->create($attributes);
    }
}
