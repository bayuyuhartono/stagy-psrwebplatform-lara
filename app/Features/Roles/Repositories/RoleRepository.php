<?php

namespace App\Features\Roles\Repositories;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Role::query()->with('permissions')->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Role
    {
        return Role::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Role $role, array $attributes): Role
    {
        $role->update($attributes);

        return $role->refresh();
    }
}
