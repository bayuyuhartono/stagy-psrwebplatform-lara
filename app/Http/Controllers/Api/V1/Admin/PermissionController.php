<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Roles\Repositories\PermissionRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PermissionResource;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function index(PermissionRepository $permissions): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return PermissionResource::collection($permissions->all());
    }
}
