<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Plans, comparison rows and add-ons are one module behind one "pricing.*"
 * permission set; each model's policy extends this one.
 */
abstract class PricingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pricing.view');
    }

    public function view(User $user, Model $entry): bool
    {
        return $user->hasPermission('pricing.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pricing.create');
    }

    public function update(User $user, Model $entry): bool
    {
        return $user->hasPermission('pricing.update');
    }

    public function delete(User $user, Model $entry): bool
    {
        return $user->hasPermission('pricing.delete');
    }

    public function restore(User $user, Model $entry): bool
    {
        return $user->hasPermission('pricing.restore');
    }
}
