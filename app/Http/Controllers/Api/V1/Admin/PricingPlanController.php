<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Pricing\Actions\CreatePricingEntryAction;
use App\Features\Pricing\Actions\DeletePricingEntryAction;
use App\Features\Pricing\Actions\RestorePricingEntryAction;
use App\Features\Pricing\Actions\UpdatePricingEntryAction;
use App\Features\Pricing\Repositories\PricingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPricingRequest;
use App\Http\Requests\Api\V1\Admin\StorePricingPlanRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePricingPlanRequest;
use App\Http\Resources\Api\V1\PricingPlanResource;
use App\Models\PricingPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PricingPlanController extends Controller
{
    public function index(IndexPricingRequest $request, PricingRepository $pricing): AnonymousResourceCollection
    {
        return PricingPlanResource::collection($pricing->ordered(PricingPlan::class, $request->locale()));
    }

    public function store(StorePricingPlanRequest $request, CreatePricingEntryAction $create): PricingPlanResource
    {
        return new PricingPlanResource($create->handle(PricingPlan::class, $request->validated()));
    }

    public function show(PricingPlan $pricingPlan): PricingPlanResource
    {
        Gate::authorize('view', $pricingPlan);

        return new PricingPlanResource($pricingPlan);
    }

    public function update(UpdatePricingPlanRequest $request, PricingPlan $pricingPlan, UpdatePricingEntryAction $update): PricingPlanResource
    {
        return new PricingPlanResource($update->handle($pricingPlan, $request->validated()));
    }

    public function destroy(PricingPlan $pricingPlan, DeletePricingEntryAction $delete): Response
    {
        Gate::authorize('delete', $pricingPlan);
        $delete->handle($pricingPlan);

        return response()->noContent();
    }

    public function restore(PricingPlan $pricingPlan, RestorePricingEntryAction $restore): PricingPlanResource
    {
        Gate::authorize('restore', $pricingPlan);

        return new PricingPlanResource($restore->handle($pricingPlan));
    }
}
