<?php

namespace App\Features\Roles\Actions;

use App\Features\Roles\Services\RoleService;
use App\Models\Role;

class DeleteRoleAction
{
    public function __construct(private RoleService $service) {}

    public function handle(Role $role): void
    {
        $this->service->delete($role);
    }
}
