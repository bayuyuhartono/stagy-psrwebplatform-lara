<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\Media\Actions\DeleteMediaAction;
use App\Features\Media\Actions\RestoreMediaAction;
use App\Features\Media\Actions\UpdateMediaAction;
use App\Features\Media\Actions\UploadMediaAction;
use App\Features\Media\Repositories\MediaRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexMediaRequest;
use App\Http\Requests\Api\V1\Admin\StoreMediaRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMediaRequest;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexMediaRequest $request, MediaRepository $media): AnonymousResourceCollection
    {
        return MediaResource::collection($media->paginate(
            $request->validated('folder'),
            $request->validated('search'),
            $request->integer('per_page', 24),
            $request->boolean('trashed'),
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaRequest $request, UploadMediaAction $upload): MediaResource
    {
        return new MediaResource($upload->handle(
            $request->user(),
            $request->file('file'),
            $request->safe()->except(['file']),
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(Media $media): MediaResource
    {
        Gate::authorize('view', $media);

        return new MediaResource($media->load('uploader'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaRequest $request, Media $media, UpdateMediaAction $update): MediaResource
    {
        return new MediaResource($update->handle($media, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media, DeleteMediaAction $delete): Response
    {
        Gate::authorize('delete', $media);
        $delete->handle($media);

        return response()->noContent();
    }

    public function restore(Media $media, RestoreMediaAction $restore): MediaResource
    {
        Gate::authorize('restore', $media);

        return new MediaResource($restore->handle($media));
    }
}
