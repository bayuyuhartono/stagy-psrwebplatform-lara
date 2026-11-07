<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'is_active' => $this->is_active,
            'locale' => $this->locale,
            'translation_group_id' => $this->translation_group_id,
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'items' => MenuItemResource::collection($this->whenLoaded('rootItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
