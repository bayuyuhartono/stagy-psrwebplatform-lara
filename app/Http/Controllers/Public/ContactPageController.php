<?php

namespace App\Http\Controllers\Public;

use App\Features\Integrations\Services\RecaptchaVerifier;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ContactPageController extends Controller
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private MenuRepository $menus,
    ) {}

    public function show(RecaptchaVerifier $recaptcha): View
    {
        return view('public.contact', [
            'settings' => $this->websiteSettings->public()->mapWithKeys(fn ($setting): array => [$setting->group->value => $setting->settings]),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
            'recaptchaSiteKey' => $recaptcha->siteKey(),
        ]);
    }
}
