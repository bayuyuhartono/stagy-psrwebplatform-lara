<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Post::class);

        return view('cms.posts.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Post::class);

        return view('cms.posts.editor');
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        return view('cms.posts.editor', ['post' => $post]);
    }
}
