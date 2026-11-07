<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Services\UserService;
use App\Models\User;

class CreateUserAction
{
    public function __construct(private UserService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): User
    {
        return $this->service->create($attributes);
    }
}
