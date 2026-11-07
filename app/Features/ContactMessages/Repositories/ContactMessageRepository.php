<?php

namespace App\Features\ContactMessages\Repositories;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageRepository
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return ContactMessage::query()->with('assignee:id,name,email')
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['assigned_to'] ?? null, fn ($query, $value) => $query->where('assigned_to', $value))
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate('submitted_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate('submitted_at', '<=', $value))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%")->orWhere('message', 'like', "%{$search}%")))
            ->latest('submitted_at')->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ContactMessage
    {
        return ContactMessage::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(ContactMessage $message, array $attributes): ContactMessage
    {
        $message->update($attributes);

        return $message->refresh();
    }
}
