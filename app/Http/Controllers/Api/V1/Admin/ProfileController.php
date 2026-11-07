<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Users\Actions\ChangePasswordAction;
use App\Features\Users\Actions\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $update): UserResource
    {
        return new UserResource($update->handle($request->user(), $request->validated()));
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $changePassword): Response
    {
        $changePassword->handle($request->user(), $request->validated('password'));

        return response()->noContent();
    }
}
