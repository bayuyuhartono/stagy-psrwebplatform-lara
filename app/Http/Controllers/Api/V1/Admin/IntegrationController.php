<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Integrations\Actions\UpdateIntegrationAction;
use App\Features\Integrations\Enums\IntegrationProvider;
use App\Features\Integrations\Repositories\IntegrationRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateIntegrationRequest;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\Integration;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class IntegrationController extends Controller
{
    public function index(IntegrationRepository $integrations): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Integration::class);

        return IntegrationResource::collection($integrations->all());
    }

    public function update(
        UpdateIntegrationRequest $request,
        string $provider,
        UpdateIntegrationAction $update,
    ): IntegrationResource {
        return new IntegrationResource($update->handle(
            IntegrationProvider::from($provider),
            $request->validated(),
        ));
    }
}
