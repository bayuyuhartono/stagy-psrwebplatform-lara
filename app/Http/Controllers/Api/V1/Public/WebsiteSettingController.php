<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WebsiteSettingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WebsiteSettingController extends Controller
{
    public function index(WebsiteSettingRepository $websiteSettings): AnonymousResourceCollection
    {
        return WebsiteSettingResource::collection($websiteSettings->public());
    }
}
