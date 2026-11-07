<?php

namespace App\Models;

use App\Features\ContactMessages\Enums\ContactFormType;
use App\Features\ContactMessages\Enums\ContactMessageStatus;
use App\Features\ContactMessages\Enums\GuestEstimate;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['assigned_to', 'form_type', 'locale', 'name', 'email', 'phone', 'whatsapp', 'company', 'subject', 'message', 'guest_estimate', 'event_date', 'interested_plan', 'consented_at', 'status', 'internal_notes', 'ip_address', 'user_agent', 'submitted_at'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $attributes = ['status' => 'new', 'form_type' => 'contact'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected function casts(): array
    {
        return [
            'status' => ContactMessageStatus::class,
            'form_type' => ContactFormType::class,
            'guest_estimate' => GuestEstimate::class,
            'event_date' => 'date',
            'consented_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
