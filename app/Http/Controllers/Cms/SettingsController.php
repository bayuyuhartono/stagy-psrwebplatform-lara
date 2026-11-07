<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Integration;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(
            Gate::allows('viewAny', WebsiteSetting::class)
            || Gate::allows('system-settings.view')
            || Gate::allows('system-health.view')
            || Gate::allows('viewAny', EmailSetting::class)
            || Gate::allows('viewAny', Integration::class)
            || Gate::allows('viewAny', FeatureFlag::class),
            403,
        );

        return view('cms.settings.index');
    }
}
