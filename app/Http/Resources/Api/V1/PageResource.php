<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'canonical_url' => (new PublicUrlGenerator)->canonicalFor($this->resource),
            'status' => $this->status->value,
            'locale' => $this->locale,
            'translation_group_id' => $this->translation_group_id,
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'blocks' => $this->blocks,
            'seo' => new SeoResource($this->whenLoaded('seo')),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
