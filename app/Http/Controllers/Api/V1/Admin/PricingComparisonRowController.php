<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Pricing\Actions\CreatePricingEntryAction;
use App\Features\Pricing\Actions\DeletePricingEntryAction;
use App\Features\Pricing\Actions\RestorePricingEntryAction;
use App\Features\Pricing\Actions\UpdatePricingEntryAction;
use App\Features\Pricing\Repositories\PricingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPricingRequest;
use App\Http\Requests\Api\V1\Admin\StorePricingComparisonRowRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePricingComparisonRowRequest;
use App\Http\Resources\Api\V1\PricingComparisonRowResource;
use App\Models\PricingComparisonRow;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PricingComparisonRowController extends Controller
{
    public function index(IndexPricingRequest $request, PricingRepository $pricing): AnonymousResourceCollection
    {
        return PricingComparisonRowResource::collection($pricing->ordered(PricingComparisonRow::class, $request->locale()));
    }

    public function store(StorePricingComparisonRowRequest $request, CreatePricingEntryAction $create): PricingComparisonRowResource
    {
        return new PricingComparisonRowResource($create->handle(PricingComparisonRow::class, $request->validated()));
    }

    public function show(PricingComparisonRow $pricingComparisonRow): PricingComparisonRowResource
    {
        Gate::authorize('view', $pricingComparisonRow);

        return new PricingComparisonRowResource($pricingComparisonRow);
    }

    public function update(UpdatePricingComparisonRowRequest $request, PricingComparisonRow $pricingComparisonRow, UpdatePricingEntryAction $update): PricingComparisonRowResource
    {
        return new PricingComparisonRowResource($update->handle($pricingComparisonRow, $request->validated()));
    }

    public function destroy(PricingComparisonRow $pricingComparisonRow, DeletePricingEntryAction $delete): Response
    {
        Gate::authorize('delete', $pricingComparisonRow);
        $delete->handle($pricingComparisonRow);

        return response()->noContent();
    }

    public function restore(PricingComparisonRow $pricingComparisonRow, RestorePricingEntryAction $restore): PricingComparisonRowResource
    {
        Gate::authorize('restore', $pricingComparisonRow);

        return new PricingComparisonRowResource($restore->handle($pricingComparisonRow));
    }
}
