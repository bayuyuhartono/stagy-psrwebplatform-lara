<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailSettingResource extends JsonResource
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
            'enabled' => $this->enabled,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'credentials_configured' => filled($this->username) || filled($this->password),
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'notification_to' => $this->notification_to,
            'auto_reply_enabled' => $this->auto_reply_enabled,
            'auto_reply_subject' => $this->auto_reply_subject,
            'auto_reply_body' => $this->auto_reply_body,
            'updated_at' => $this->updated_at,
        ];
    }
}
