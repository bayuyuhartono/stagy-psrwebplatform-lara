<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Posts\Services\ReadingTimeEstimator;
use App\Features\Seo\Services\PublicUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'type' => $this->type->value,
            'status' => $this->status->value,
            'locale' => $this->locale,
            'translation_group_id' => $this->translation_group_id,
            'translations' => TranslationResource::collection($this->whenLoaded('translations')),
            'excerpt' => $this->excerpt,
            'is_featured' => $this->is_featured,
            'reading_time_minutes' => (new ReadingTimeEstimator)->minutes($this->resource),
            'cover_image' => new MediaResource($this->whenLoaded('coverImage')),
            'blocks' => $this->blocks,
            'author' => $this->whenLoaded('author', fn (): ?array => $this->author ? ['id' => $this->author->id, 'name' => $this->author->name] : null),
            'categories' => TaxonomyResource::collection($this->whenLoaded('categories')),
            'tags' => TaxonomyResource::collection($this->whenLoaded('tags')),
            'seo' => new SeoResource($this->whenLoaded('seo')),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
