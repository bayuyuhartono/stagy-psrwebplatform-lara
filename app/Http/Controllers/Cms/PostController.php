<?php

namespace App\Http\Controllers\Cms;

use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\Post;
use App\Models\Seo;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Post::class);

        return view('cms.posts.index');
    }

    public function create(PublicWebsiteSettings $settings): View
    {
        Gate::authorize('create', Post::class);

        return view('cms.posts.editor', $this->editorData($settings));
    }

    public function edit(Post $post, PublicWebsiteSettings $settings): View
    {
        Gate::authorize('update', $post);

        return view('cms.posts.editor', ['post' => $post, ...$this->editorData($settings)]);
    }

    /**
     * What the editor's search & social panel needs: whether to show it at all,
     * whether it can be saved, and the site name the search preview appends.
     *
     * @return array{seoPanel: array{visible: bool, editable: bool, siteName: string}}
     */
    private function editorData(PublicWebsiteSettings $settings): array
    {
        $seoEnabled = FeatureFlag::query()->where('name', 'seo')->where('enabled', true)->exists();

        return [
            'seoPanel' => [
                'visible' => $seoEnabled && Gate::allows('viewAny', Seo::class),
                'editable' => $seoEnabled && request()->user()->hasPermission('seo.update'),
                'siteName' => $settings->forCurrentLocale()->get('general', [])['site_name'] ?? config('app.name'),
            ],
        ];
    }
}
