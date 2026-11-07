<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'slug' => $this->slug,
            'canonical_url' => (new PublicUrlGenerator)->canonicalFor($this->resource), 'excerpt' => $this->excerpt,
            'client' => $this->client, 'project_date' => $this->project_date, 'external_url' => $this->external_url,
            'status' => $this->status->value, 'is_featured' => $this->is_featured, 'blocks' => $this->blocks, 'position' => $this->position,
            'author' => $this->whenLoaded('author', fn (): ?array => $this->author ? ['id' => $this->author->id, 'name' => $this->author->name] : null),
            'cover_image' => new MediaResource($this->whenLoaded('coverImage')),
            'categories' => TaxonomyResource::collection($this->whenLoaded('categories')),
            'tags' => TaxonomyResource::collection($this->whenLoaded('tags')),
            'seo' => new SeoResource($this->whenLoaded('seo')),
            'published_at' => $this->published_at, 'created_at' => $this->created_at, 'updated_at' => $this->updated_at,
        ];
    }
}
