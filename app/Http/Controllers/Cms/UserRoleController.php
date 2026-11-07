<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(Gate::allows('viewAny', User::class) || Gate::allows('viewAny', Role::class), 403);

        return view('cms.users-roles.index');
    }
}
