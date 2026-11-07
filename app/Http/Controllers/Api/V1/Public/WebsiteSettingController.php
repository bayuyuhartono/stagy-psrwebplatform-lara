<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WebsiteSettingResource;
use App\Models\WebsiteSetting;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebsiteSettingController extends Controller
{
    /**
     * Per-language settings are returned resolved to the requested ?locale.
     */
    public function index(WebsiteSettingRepository $websiteSettings, PublicWebsiteSettings $publicSettings): AnonymousResourceCollection
    {
        return WebsiteSettingResource::collection(
            $websiteSettings->public()->each(fn (WebsiteSetting $setting) => $setting->settings = $publicSettings->localize($setting)),
        );
    }
}
