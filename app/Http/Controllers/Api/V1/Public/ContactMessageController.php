<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\ContactMessages\Actions\SubmitContactMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\StoreContactMessageRequest;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request, SubmitContactMessageAction $submit): JsonResponse
    {
        $message = $submit->handle([
            ...$request->submissionData(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['data' => ['id' => $message->id, 'status' => $message->status->value]], 201);
    }
}
