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
            'form_type' => $this->form_type->value,
            'locale' => $this->locale,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'subject' => $this->subject,
            'message' => $this->message,
            'whatsapp' => $this->whatsapp,
            'guest_estimate' => $this->guest_estimate?->value,
            'guest_estimate_label' => $this->guest_estimate?->label('en'),
            'event_date' => $this->event_date?->toDateString(),
            'interested_plan' => $this->interested_plan,
            'consented_at' => $this->consented_at,
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
