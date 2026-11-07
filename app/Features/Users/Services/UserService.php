<?php

namespace App\Features\Users\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Users\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Arr;

class UserService
{
    public function __construct(
        private UserRepository $users,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        $roleIds = Arr::pull($attributes, 'role_ids', []);
        $user = $this->users->create($attributes);
        $user->roles()->sync($roleIds);
        $this->auditLogger->record('user.created', $user, newValues: $this->auditableValues($user));

        return $user->load('roles');
    }

    /** @param array<string, mixed> $attributes */
    public function update(User $user, array $attributes): User
    {
        $oldValues = $this->auditableValues($user);
        $roleIds = Arr::pull($attributes, 'role_ids');
        $updatedUser = $this->users->update($user, $attributes);

        if ($roleIds !== null) {
            $updatedUser->roles()->sync($roleIds);
        }

        $this->auditLogger->record('user.updated', $updatedUser, $oldValues, $this->auditableValues($updatedUser));

        return $updatedUser->load('roles');
    }

    public function delete(User $user): void
    {
        $this->auditLogger->record('user.deleted', $user, oldValues: $this->auditableValues($user));
        $user->tokens()->delete();
        $user->delete();
    }

    public function restore(User $user): User
    {
        $user->restore();
        $this->auditLogger->record('user.restored', $user, newValues: $this->auditableValues($user));

        return $user->load('roles');
    }

    /** @param array<string, mixed> $attributes */
    public function updateProfile(User $user, array $attributes): User
    {
        return $this->update($user, $attributes);
    }

    public function changePassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
        $user->tokens()->delete();
        $this->auditLogger->record('user.password_changed', $user);
    }

    /** @return array<string, mixed> */
    private function auditableValues(User $user): array
    {
        return Arr::except($user->getAttributes(), ['password', 'remember_token']);
    }
}
