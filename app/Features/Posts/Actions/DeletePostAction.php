<?php

namespace App\Features\Posts\Actions;

use App\Features\Posts\Services\PostService;
use App\Models\Post;

class DeletePostAction
{
    public function __construct(private PostService $service) {}

    public function handle(Post $post): void
    {
        $this->service->delete($post);
    }
}
