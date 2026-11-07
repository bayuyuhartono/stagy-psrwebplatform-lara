<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\WebsiteSettings\Actions\UpdateWebsiteSettingAction;
use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateWebsiteSettingRequest;
use App\Http\Resources\Api\V1\WebsiteSettingResource;
use App\Models\WebsiteSetting;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WebsiteSettingController extends Controller
{
    public function index(WebsiteSettingRepository $websiteSettings): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', WebsiteSetting::class);

        return WebsiteSettingResource::collection($websiteSettings->all());
    }

    public function update(
        UpdateWebsiteSettingRequest $request,
        string $group,
        UpdateWebsiteSettingAction $update,
    ): WebsiteSettingResource {
        return new WebsiteSettingResource($update->handle(
            WebsiteSettingGroup::from($group),
            $request->validated(),
        ));
    }
}
