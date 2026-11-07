<?php

namespace App\Features\Users\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->with('roles')->latest()->paginate($perPage);
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }
}
