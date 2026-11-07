<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationResource extends JsonResource
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
            'provider' => $this->provider->value,
            'enabled' => $this->enabled,
            'config' => $this->public_config,
            'configured_secrets' => array_keys(array_filter($this->secret_config ?? [])),
            'updated_at' => $this->updated_at,
        ];
    }
}
