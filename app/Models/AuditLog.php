<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUuids, MassPrunable;

    /**
     * Entries older than the configured retention window.
     *
     * A retention of 0 disables pruning by matching nothing — `whereRaw('0 = 1')`
     * rather than an empty builder, since an unconstrained prunable query would
     * delete the entire table.
     */
    public function prunable(): Builder
    {
        $days = (int) config('audit.retention_days', 365);

        if ($days <= 0) {
            return static::query()->whereRaw('0 = 1');
        }

        return static::query()->where('created_at', '<', now()->subDays($days));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }
}
