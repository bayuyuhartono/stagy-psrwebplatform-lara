<?php

namespace App\Http\Resources\Api\V1;

use App\Features\Seo\Services\PublicUrlGenerator;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A summary of one member of a translation group, used to link a record to
 * its counterparts in other locales.
 */
class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPublicContent = $this->resource instanceof Page || $this->resource instanceof Post;

        return [
            'id' => $this->id,
            'locale' => $this->locale,
            'title' => $this->resource->getAttribute('title') ?? $this->resource->getAttribute('name'),
            'slug' => $this->resource->getAttribute('slug') ?? $this->resource->getAttribute('location'),
            'status' => $this->resource->getAttribute('status')?->value,
            'url' => $this->when($isPublicContent, fn (): string => (new PublicUrlGenerator)->for($this->resource)),
        ];
    }
}
