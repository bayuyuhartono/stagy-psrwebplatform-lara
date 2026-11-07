<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\Integrations\Repositories\IntegrationRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicIntegrationResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IntegrationController extends Controller
{
    public function index(IntegrationRepository $integrations): AnonymousResourceCollection
    {
        return PublicIntegrationResource::collection($integrations->enabled());
    }
}
