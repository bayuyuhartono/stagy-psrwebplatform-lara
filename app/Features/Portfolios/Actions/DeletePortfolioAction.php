<?php

namespace App\Features\Portfolios\Actions;

use App\Features\Portfolios\Services\PortfolioService;
use App\Models\Portfolio;

class DeletePortfolioAction
{
    public function __construct(private PortfolioService $service) {}

    public function handle(Portfolio $portfolio): void
    {
        $this->service->delete($portfolio);
    }
}
