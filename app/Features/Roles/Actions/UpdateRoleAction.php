<?php

namespace App\Features\Roles\Actions;

use App\Features\Roles\Services\RoleService;
use App\Models\Role;

class UpdateRoleAction
{
    public function __construct(private RoleService $service) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Role $role, array $attributes): Role
    {
        return $this->service->update($role, $attributes);
    }
}
