<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Roles\Actions\CreateRoleAction;
use App\Features\Roles\Actions\DeleteRoleAction;
use App\Features\Roles\Actions\UpdateRoleAction;
use App\Features\Roles\Repositories\RoleRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Models\Role;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoleRepository $roles): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return RoleResource::collection($roles->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, CreateRoleAction $create): RoleResource
    {
        return new RoleResource($create->handle($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): RoleResource
    {
        Gate::authorize('view', $role);

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $update): RoleResource
    {
        return new RoleResource($update->handle($role, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role, DeleteRoleAction $delete): Response
    {
        Gate::authorize('delete', $role);
        $delete->handle($role);

        return response()->noContent();
    }
}
