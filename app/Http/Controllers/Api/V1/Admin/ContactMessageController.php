<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\ContactMessages\Actions\DeleteContactMessageAction;
use App\Features\ContactMessages\Actions\RestoreContactMessageAction;
use App\Features\ContactMessages\Actions\UpdateContactMessageAction;
use App\Features\ContactMessages\Repositories\ContactMessageRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexContactMessageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateContactMessageRequest;
use App\Http\Resources\Api\V1\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ContactMessageController extends Controller
{
    public function index(IndexContactMessageRequest $request, ContactMessageRepository $messages): AnonymousResourceCollection
    {
        return ContactMessageResource::collection($messages->paginate($request->validated(), $request->integer('per_page', 15)));
    }

    public function show(ContactMessage $contactMessage): ContactMessageResource
    {
        Gate::authorize('view', $contactMessage);

        return new ContactMessageResource($contactMessage->load('assignee:id,name,email'));
    }

    public function update(UpdateContactMessageRequest $request, ContactMessage $contactMessage, UpdateContactMessageAction $update): ContactMessageResource
    {
        return new ContactMessageResource($update->handle($contactMessage, $request->validated()));
    }

    public function destroy(ContactMessage $contactMessage, DeleteContactMessageAction $delete): Response
    {
        Gate::authorize('delete', $contactMessage);
        $delete->handle($contactMessage);

        return response()->noContent();
    }

    public function restore(ContactMessage $contactMessage, RestoreContactMessageAction $restore): ContactMessageResource
    {
        Gate::authorize('restore', $contactMessage);

        return new ContactMessageResource($restore->handle($contactMessage));
    }
}
