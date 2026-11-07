<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Audit\Repositories\AuditLogRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(AuditLogRepository $auditLogs): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditLog::class);

        return AuditLogResource::collection($auditLogs->paginate());
    }
}
