<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'excerpt' => $this->excerpt,
            'icon' => $this->icon,
            'status' => $this->status->value,
            'blocks' => $this->blocks,
            'position' => $this->position,
            'author' => $this->whenLoaded('author', fn (): ?array => $this->author ? ['id' => $this->author->id, 'name' => $this->author->name] : null),
            'seo' => new SeoResource($this->whenLoaded('seo')),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
