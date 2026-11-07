<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type->value,
            'url' => (new PublicUrlGenerator)->forMenuItem($this->resource),
            'target' => $this->target->value,
            'position' => $this->position,
            'page' => $this->when($this->page !== null, fn (): array => ['id' => $this->page->id, 'slug' => $this->page->slug]),
            'children' => MenuItemResource::collection($this->whenLoaded('childrenRecursive')),
        ];
    }
}
