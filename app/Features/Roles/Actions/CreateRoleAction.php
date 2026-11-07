<?php

namespace App\Features\Roles\Actions;

use App\Features\Roles\Services\RoleService;
use App\Models\Role;

class CreateRoleAction
{
    public function __construct(private RoleService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Role
    {
        return $this->service->create($attributes);
    }
}
