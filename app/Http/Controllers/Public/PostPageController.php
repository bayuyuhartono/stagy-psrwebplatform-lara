<?php

namespace App\Http\Controllers\Public;

use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Posts\Enums\PostType;
use App\Features\Posts\Repositories\PostRepository;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\View\View;

class PostPageController extends Controller
{
    public function __construct(
        private WebsiteSettingRepository $websiteSettings,
        private MenuRepository $menus,
    ) {}

    public function blog(PostRepository $posts): View
    {
        return $this->index($posts, PostType::Blog);
    }

    public function blogPost(string $slug, PostRepository $posts): View
    {
        return $this->show($posts, PostType::Blog, $slug);
    }

    public function news(PostRepository $posts): View
    {
        return $this->index($posts, PostType::News);
    }

    public function newsPost(string $slug, PostRepository $posts): View
    {
        return $this->show($posts, PostType::News, $slug);
    }

    public function blogCategory(string $slug, PostRepository $posts): View
    {
        return $this->archive($posts, PostType::Blog, 'category', $slug);
    }

    public function blogTag(string $slug, PostRepository $posts): View
    {
        return $this->archive($posts, PostType::Blog, 'tag', $slug);
    }

    public function newsCategory(string $slug, PostRepository $posts): View
    {
        return $this->archive($posts, PostType::News, 'category', $slug);
    }

    public function newsTag(string $slug, PostRepository $posts): View
    {
        return $this->archive($posts, PostType::News, 'tag', $slug);
    }

    private function index(PostRepository $posts, PostType $type): View
    {
        return view('public.posts.index', [
            ...$this->layoutData(),
            'posts' => $posts->paginatePublished(['type' => $type->value], 10),
            'type' => $type,
        ]);
    }

    /**
     * Archive listing filtered to a single category or tag.
     *
     * 404s on an unknown taxonomy rather than rendering an empty list, so that
     * mistyped archive URLs are not indexable as thin pages.
     */
    private function archive(PostRepository $posts, PostType $type, string $taxonomy, string $slug): View
    {
        $model = $taxonomy === 'category' ? Category::class : Tag::class;
        $term = $model::query()->where('slug', $slug)->firstOrFail();

        return view('public.posts.index', [
            ...$this->layoutData(),
            'posts' => $posts->paginatePublished(['type' => $type->value, $taxonomy => $slug], 10),
            'type' => $type,
            'archive' => ['taxonomy' => $taxonomy, 'term' => $term],
        ]);
    }

    private function show(PostRepository $posts, PostType $type, string $slug): View
    {
        return view('public.posts.show', [
            ...$this->layoutData(),
            'post' => $posts->findPublishedBySlugAndType($slug, $type),
            'type' => $type,
        ]);
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'settings' => $this->websiteSettings->public()->mapWithKeys(fn ($setting): array => [$setting->group->value => $setting->settings]),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
        ];
    }
}
