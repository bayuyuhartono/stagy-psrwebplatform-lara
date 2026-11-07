<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Settings\Actions\UpdateFeatureFlagAction;
use App\Features\Settings\Repositories\FeatureFlagRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateFeatureFlagRequest;
use App\Http\Resources\Api\V1\FeatureFlagResource;
use App\Models\FeatureFlag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FeatureFlagController extends Controller
{
    public function index(FeatureFlagRepository $featureFlags): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', FeatureFlag::class);

        return FeatureFlagResource::collection($featureFlags->all());
    }

    public function update(UpdateFeatureFlagRequest $request, FeatureFlag $featureFlag, UpdateFeatureFlagAction $update): FeatureFlagResource
    {
        return new FeatureFlagResource($update->handle($featureFlag, $request->validated()));
    }
}
