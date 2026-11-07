<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Services\UserService;
use App\Models\User;

class DeleteUserAction
{
    public function __construct(private UserService $service) {}

    public function handle(User $user): void
    {
        $this->service->delete($user);
    }
}
