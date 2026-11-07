<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Services\UserService;
use App\Models\User;

class RestoreUserAction
{
    public function __construct(private UserService $service) {}

    public function handle(User $user): User
    {
        return $this->service->restore($user);
    }
}
