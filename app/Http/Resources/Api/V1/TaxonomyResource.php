<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonomyResource extends JsonResource
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
            'slug' => $this->slug,
            'locale' => $this->whenHas('locale'),
            'translation_group_id' => $this->whenHas('translation_group_id'),
            'posts_count' => $this->whenCounted('posts'),
            'portfolios_count' => $this->whenCounted('portfolios'),
        ];
    }
}
