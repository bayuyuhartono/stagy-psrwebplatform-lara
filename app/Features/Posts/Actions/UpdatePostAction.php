<?php

namespace App\Features\Posts\Actions;

use App\Features\Posts\Services\PostService;
use App\Models\Post;

class UpdatePostAction
{
    public function __construct(private PostService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(Post $post, array $attributes): Post
    {
        return $this->service->update($post, $attributes);
    }
}
