<?php

namespace App\Features\Roles\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Roles\Repositories\RoleRepository;
use App\Models\Role;
use Illuminate\Support\Arr;

class RoleService
{
    public function __construct(
        private RoleRepository $roles,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Role
    {
        $permissionIds = Arr::pull($attributes, 'permission_ids', []);
        $role = $this->roles->create($attributes);
        $role->permissions()->sync($permissionIds);
        $this->auditLogger->record('role.created', $role, newValues: $role->getAttributes());

        return $role->load('permissions');
    }

    /** @param array<string, mixed> $attributes */
    public function update(Role $role, array $attributes): Role
    {
        $oldValues = $role->getAttributes();
        $permissionIds = Arr::pull($attributes, 'permission_ids');
        $updatedRole = $this->roles->update($role, $attributes);

        if ($permissionIds !== null) {
            $updatedRole->permissions()->sync($permissionIds);
        }

        $this->auditLogger->record('role.updated', $updatedRole, $oldValues, $updatedRole->getAttributes());

        return $updatedRole->load('permissions');
    }

    public function delete(Role $role): void
    {
        $this->auditLogger->record('role.deleted', $role, oldValues: $role->getAttributes());
        $role->delete();
    }
}
