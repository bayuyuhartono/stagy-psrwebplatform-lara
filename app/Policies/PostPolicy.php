<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('posts.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.view');
    }

    /**
     * `posts.update` alone scopes editing to posts the user authored; the separate
     * `posts.update.any` grant lifts that restriction.
     *
     * Kept explicit rather than inferred from delete/restore so that update scope
     * never changes as a side effect of granting an unrelated permission.
     */
    private function canEditPostsByOthers(User $user, Post $post): bool
    {
        if ($user->hasPermission('posts.update.any')) {
            return true;
        }

        return $post->author_id !== null && $post->author_id === $user->getKey();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('posts.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.update') && $this->canEditPostsByOthers($user, $post);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->hasPermission('posts.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return false;
    }
}
