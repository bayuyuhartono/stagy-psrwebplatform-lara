<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Pricing\Actions\CreatePricingEntryAction;
use App\Features\Pricing\Actions\DeletePricingEntryAction;
use App\Features\Pricing\Actions\RestorePricingEntryAction;
use App\Features\Pricing\Actions\UpdatePricingEntryAction;
use App\Features\Pricing\Repositories\PricingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPricingRequest;
use App\Http\Requests\Api\V1\Admin\StorePricingAddonRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePricingAddonRequest;
use App\Http\Resources\Api\V1\PricingAddonResource;
use App\Models\PricingAddon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PricingAddonController extends Controller
{
    public function index(IndexPricingRequest $request, PricingRepository $pricing): AnonymousResourceCollection
    {
        return PricingAddonResource::collection($pricing->ordered(PricingAddon::class, $request->locale()));
    }

    public function store(StorePricingAddonRequest $request, CreatePricingEntryAction $create): PricingAddonResource
    {
        return new PricingAddonResource($create->handle(PricingAddon::class, $request->validated()));
    }

    public function show(PricingAddon $pricingAddon): PricingAddonResource
    {
        Gate::authorize('view', $pricingAddon);

        return new PricingAddonResource($pricingAddon);
    }

    public function update(UpdatePricingAddonRequest $request, PricingAddon $pricingAddon, UpdatePricingEntryAction $update): PricingAddonResource
    {
        return new PricingAddonResource($update->handle($pricingAddon, $request->validated()));
    }

    public function destroy(PricingAddon $pricingAddon, DeletePricingEntryAction $delete): Response
    {
        Gate::authorize('delete', $pricingAddon);
        $delete->handle($pricingAddon);

        return response()->noContent();
    }

    public function restore(PricingAddon $pricingAddon, RestorePricingEntryAction $restore): PricingAddonResource
    {
        Gate::authorize('restore', $pricingAddon);

        return new PricingAddonResource($restore->handle($pricingAddon));
    }
}
