<?php

namespace App\Features\Roles\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository
{
    /** @return Collection<int, Permission> */
    public function all(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }
}
