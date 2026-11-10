<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * The signed-in user's own account page. Every user may manage their own
     * password, so no permission check beyond authentication applies.
     */
    public function __invoke(): View
    {
        return view('cms.profile.index');
    }
}
