<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\EmailSettings\Actions\SendTestEmailAction;
use App\Features\EmailSettings\Actions\UpdateEmailSettingAction;
use App\Features\EmailSettings\Repositories\EmailSettingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SendTestEmailRequest;
use App\Http\Requests\Api\V1\Admin\UpdateEmailSettingRequest;
use App\Http\Resources\Api\V1\EmailSettingResource;
use App\Models\EmailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EmailSettingController extends Controller
{
    public function show(EmailSettingRepository $emailSettings): EmailSettingResource
    {
        Gate::authorize('viewAny', EmailSetting::class);

        return new EmailSettingResource($emailSettings->get());
    }

    public function update(UpdateEmailSettingRequest $request, UpdateEmailSettingAction $update): EmailSettingResource
    {
        return new EmailSettingResource($update->handle($request->validated()));
    }

    public function test(SendTestEmailRequest $request, SendTestEmailAction $send): JsonResponse
    {
        $send->handle($request->validated('recipient'));

        return response()->json(['message' => 'Test email sent successfully.']);
    }
}
