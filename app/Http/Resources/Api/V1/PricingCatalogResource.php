<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public pricing page for one locale: plans, the comparison table (rows
 * that differ between plans), what every plan includes, and add-ons.
 *
 * Comparison cells line up with `plans` by position.
 */
class PricingCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plans' => PricingPlanResource::collection($this->resource['plans'])->resolve($request),
            'comparison' => $this->resource['groups'],
            'included_in_all' => $this->resource['included_in_all'],
            'addons' => PricingAddonResource::collection($this->resource['addons'])->resolve($request),
        ];
    }
}
