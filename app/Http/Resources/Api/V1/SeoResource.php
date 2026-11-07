<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoResource extends JsonResource
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
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'focus_keyword' => $this->focus_keyword,
            'canonical_url' => $this->canonical_url,
            'robots' => ['index' => $this->robots_index, 'follow' => $this->robots_follow],
            'open_graph' => [
                'title' => $this->og_title,
                'description' => $this->og_description,
                'image' => new MediaResource($this->whenLoaded('ogImage')),
            ],
            'twitter_card' => $this->twitter_card,
            'structured_data' => $this->structured_data,
            'score' => $this->score,
            'updated_at' => $this->updated_at,
        ];
    }
}
