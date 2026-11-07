<?php

namespace App\Features\Portfolios\Actions;

use App\Features\Portfolios\Services\PortfolioService;
use App\Models\Portfolio;

class UpdatePortfolioAction
{
    public function __construct(private PortfolioService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(Portfolio $portfolio, array $attributes): Portfolio
    {
        return $this->service->update($portfolio, $attributes);
    }
}
