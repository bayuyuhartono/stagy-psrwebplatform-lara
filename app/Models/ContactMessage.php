<?php

namespace App\Models;

use App\Features\ContactMessages\Enums\ContactMessageStatus;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['assigned_to', 'name', 'email', 'phone', 'company', 'subject', 'message', 'status', 'internal_notes', 'ip_address', 'user_agent', 'submitted_at'])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $attributes = ['status' => 'new'];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected function casts(): array
    {
        return ['status' => ContactMessageStatus::class, 'submitted_at' => 'datetime'];
    }
}
