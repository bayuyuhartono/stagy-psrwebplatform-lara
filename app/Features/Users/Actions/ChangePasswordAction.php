<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Services\UserService;
use App\Models\User;

class ChangePasswordAction
{
    public function __construct(private UserService $service) {}

    public function handle(User $user, string $password): void
    {
        $this->service->changePassword($user, $password);
    }
}
