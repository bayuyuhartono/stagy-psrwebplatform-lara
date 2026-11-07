<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Users\Actions\CreateUserAction;
use App\Features\Users\Actions\DeleteUserAction;
use App\Features\Users\Actions\RestoreUserAction;
use App\Features\Users\Actions\UpdateUserAction;
use App\Features\Users\Repositories\UserRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UserRepository $users): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection($users->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, CreateUserAction $create): UserResource
    {
        return new UserResource($create->handle($request->validated()));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $update): UserResource
    {
        return new UserResource($update->handle($user, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, DeleteUserAction $delete): Response
    {
        Gate::authorize('delete', $user);
        $delete->handle($user);

        return response()->noContent();
    }

    public function restore(User $user, RestoreUserAction $restore): UserResource
    {
        Gate::authorize('restore', $user);

        return new UserResource($restore->handle($user));
    }
}
