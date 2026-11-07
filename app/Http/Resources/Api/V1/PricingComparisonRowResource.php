<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingComparisonRowResource extends JsonResource
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
            'group_name' => $this->group_name,
            'label' => $this->label,
            // An empty PHP array would encode as [], so force an object for clients keyed by plan id.
            'values' => (object) $this->values,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
