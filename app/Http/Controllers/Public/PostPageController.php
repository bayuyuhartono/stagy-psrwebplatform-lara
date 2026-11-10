<?php

namespace App\Http\Controllers\Public;

use App\Features\ContentBlocks\Services\BlockMediaResolver;
use App\Features\Menus\Repositories\MenuRepository;
use App\Features\Pages\Repositories\PageRepository;
use App\Features\Posts\Enums\PostType;
use App\Features\Posts\Repositories\PostRepository;
use App\Features\Taxonomies\Repositories\TaxonomyRepository;
use App\Features\WebsiteSettings\Services\PublicWebsiteSettings;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostPageController extends Controller
{
    public function __construct(
        private PublicWebsiteSettings $publicSettings,
        private MenuRepository $menus,
        private TaxonomyRepository $taxonomies,
        private PageRepository $pages,
        private BlockMediaResolver $blockMedia,
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
            ...$this->listingData($type),
            'posts' => $posts->paginatePublished(['type' => $type->value], 10),
            // Featured only heads the first page; themes skip it in the grid below.
            'featured' => request()->integer('page', 1) <= 1 ? $posts->findFeaturedPublished($type) : null,
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
        $term = $taxonomy === 'category'
            ? $this->taxonomies->findCategoryBySlug($slug)
            : Tag::query()->where('slug', $slug)->firstOrFail();

        return view('public.posts.index', [
            ...$this->listingData($type),
            'posts' => $posts->paginatePublished(['type' => $type->value, $taxonomy => $slug], 10),
            'featured' => null,
            'archive' => ['taxonomy' => $taxonomy, 'term' => $term],
        ]);
    }

    /**
     * The saved post exactly as visitors would see it, whatever its status, for
     * CMS users who may edit it. Served from the CMS (cms.posts.preview), never indexed.
     */
    public function preview(Post $post, PostRepository $posts): Response
    {
        Gate::authorize('update', $post);
        App::setLocale($post->locale);
        $post->load(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'seo.ogImage', 'coverImage']);
        // Shared so the theme layout (a component, which does not see view data) can show the preview banner.
        view()->share(['previewing' => true, 'previewEditUrl' => route('cms.posts.edit', $post)]);

        return response()
            ->view('public.posts.show', [
                ...$this->layoutData(),
                'post' => $post,
                'type' => $post->type,
                // One more than the 3 listed below the article: the closest match is suggested mid-article.
                'related' => $posts->relatedTo($post, 4),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function show(PostRepository $posts, PostType $type, string $slug): View
    {
        $post = $posts->findPublishedBySlugAndType($slug, $type);

        return view('public.posts.show', [
            ...$this->layoutData(),
            'post' => $post,
            'type' => $type,
            // One more than the 3 listed below the article: the closest match is suggested mid-article.
            'related' => $posts->relatedTo($post, 4),
        ]);
    }

    /**
     * Shared by the listing and its archives: the categories to filter by and
     * the CMS page whose slug matches the listing ("blog"), which themes may use
     * for the listing's heading, closing blocks and SEO.
     *
     * @return array<string, mixed>
     */
    private function listingData(PostType $type): array
    {
        $introPage = $this->pages->findPublishedBySlugOrNull($type->value);

        return [
            ...$this->layoutData(),
            'type' => $type,
            'categories' => $this->taxonomies->categoriesWithPublishedPosts($type),
            'introPage' => $introPage,
            'blockMedia' => $this->blockMedia->resolve($introPage?->blocks),
        ];
    }

    /** @return array<string, mixed> */
    private function layoutData(): array
    {
        return [
            'settings' => $this->publicSettings->forCurrentLocale(),
            'menu' => $this->menus->findActiveByLocationOrNull('header'),
        ];
    }
}
