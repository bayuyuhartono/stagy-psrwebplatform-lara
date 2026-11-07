<?php

namespace App\Features\Audit\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return AuditLog::query()->with('user')->latest()->paginate($perPage);
    }
}
