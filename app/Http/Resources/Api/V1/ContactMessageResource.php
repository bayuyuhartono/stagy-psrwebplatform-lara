<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status->value,
            'internal_notes' => $this->internal_notes,
            'assignee' => $this->whenLoaded('assignee', fn (): ?array => $this->assignee ? ['id' => $this->assignee->id, 'name' => $this->assignee->name, 'email' => $this->assignee->email] : null),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'submitted_at' => $this->submitted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
