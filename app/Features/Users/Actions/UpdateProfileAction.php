<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Services\UserService;
use App\Models\User;

class UpdateProfileAction
{
    public function __construct(private UserService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $user, array $attributes): User
    {
        return $this->service->updateProfile($user, $attributes);
    }
}
