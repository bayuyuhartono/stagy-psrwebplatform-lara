<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Pricing\Services\PricingCatalog;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PricingCatalogResource;

class PricingController extends Controller
{
    public function show(PricingCatalog $catalog): PricingCatalogResource
    {
        return new PricingCatalogResource($catalog->for());
    }
}
