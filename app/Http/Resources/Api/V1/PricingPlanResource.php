<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Pricing\Services\PriceFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $formatter = new PriceFormatter;

        return [
            'id' => $this->id,
            'locale' => $this->locale,
            'translation_group_id' => $this->translation_group_id,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'price' => $this->price,
            'price_formatted' => $formatter->format($this->price, $this->locale),
            'extra_guest_price' => $this->extra_guest_price,
            'extra_guest_price_formatted' => $this->extra_guest_price !== null ? $formatter->format($this->extra_guest_price, $this->locale) : null,
            'features' => $this->features,
            'support_summary' => $this->support_summary,
            'is_highlighted' => $this->is_highlighted,
            'highlight_label' => $this->highlight_label,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
