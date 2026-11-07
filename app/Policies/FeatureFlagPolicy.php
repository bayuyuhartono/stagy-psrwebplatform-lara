<?php

namespace App\Policies;

use App\Models\FeatureFlag;
use App\Models\User;

class FeatureFlagPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feature-flags.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->hasPermission('feature-flags.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('feature-flags.update');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FeatureFlag $featureFlag): bool
    {
        return $user->hasPermission('feature-flags.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FeatureFlag $featureFlag): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FeatureFlag $featureFlag): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FeatureFlag $featureFlag): bool
    {
        return false;
    }
}
