<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', ContactMessage::class);

        return view('cms.contact-messages.index');
    }
}
