<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'url' => $this->url,
            'disk' => $this->disk,
            'path' => $this->path,
            'folder' => $this->folder,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'metadata' => $this->metadata,
            'usage' => $this->mediable_type ? [
                'type' => $this->mediable_type,
                'id' => $this->mediable_id,
            ] : null,
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
