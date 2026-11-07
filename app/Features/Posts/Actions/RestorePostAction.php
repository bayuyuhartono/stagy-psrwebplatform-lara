<?php

namespace App\Features\Posts\Actions;

use App\Features\Posts\Services\PostService;
use App\Models\Post;

class RestorePostAction
{
    public function __construct(private PostService $service) {}

    public function handle(Post $post): Post
    {
        return $this->service->restore($post);
    }
}
