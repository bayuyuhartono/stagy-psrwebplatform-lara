<?php

namespace App\Features\Portfolios\Actions;

use App\Features\Portfolios\Services\PortfolioService;
use App\Models\Portfolio;

class RestorePortfolioAction
{
    public function __construct(private PortfolioService $service) {}

    public function handle(Portfolio $portfolio): Portfolio
    {
        return $this->service->restore($portfolio);
    }
}
