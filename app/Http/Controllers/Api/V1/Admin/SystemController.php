<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Features\System\Actions\GetSystemHealthAction;
use App\Features\System\Actions\GetSystemSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SystemHealthResource;
use App\Http\Resources\Api\V1\SystemSettingResource;
use Illuminate\Support\Facades\Gate;

class SystemController extends Controller
{
    public function settings(GetSystemSettingsAction $settings): SystemSettingResource
    {
        Gate::authorize('system-settings.view');

        return new SystemSettingResource($settings->handle());
    }

    public function health(GetSystemHealthAction $health): SystemHealthResource
    {
        Gate::authorize('system-health.view');

        return new SystemHealthResource($health->handle());
    }
}
