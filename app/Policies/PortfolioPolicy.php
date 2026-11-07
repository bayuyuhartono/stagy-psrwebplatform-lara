<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('portfolio.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('portfolio.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('portfolio.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('portfolio.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('portfolio.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Portfolio $portfolio): bool
    {
        return $user->hasPermission('portfolio.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Portfolio $portfolio): bool
    {
        return false;
    }
}
