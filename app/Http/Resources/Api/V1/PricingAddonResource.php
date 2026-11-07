<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Pricing\Services\PriceFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingAddonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'locale' => $this->locale,
            'translation_group_id' => $this->translation_group_id,
            'name' => $this->name,
            'price' => $this->price,
            'price_formatted' => (new PriceFormatter)->format($this->price, $this->locale),
            'unit' => $this->unit,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
