<x-cms.app-layout :title="isset($post) ? 'Edit post' : 'New post'" section="posts">
    <section data-page-editor data-resource="posts" data-page-id="{{ $post->id ?? '' }}" data-index-url="{{ route('cms.posts.index') }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0">
                <a href="{{ route('cms.posts.index') }}" class="text-xs font-semibold text-cms-green">← Back to posts</a>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-cms-navy">{{ isset($post) ? 'Edit post' : 'Create a new post' }}</h2>
                <p class="mt-1 text-sm text-cms-navy/50">Write a blog article or company news update.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a data-post-view href="#" target="_blank" rel="noopener" class="hidden h-11 items-center rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold text-cms-navy">View on site ↗</a>
                @isset($post)
                    @can('delete', $post)
                        <button data-delete-page type="button" class="h-11 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Delete</button>
                    @endcan
                @endisset
                <button data-save-page type="submit" form="page-editor-form" class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Save post</button>
            </div>
        </div>

        <div data-page-notice class="hidden rounded-xl border p-4 text-sm"></div>

        <form id="page-editor-form" class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid min-w-0 content-start gap-6">
                <section class="grid gap-5 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold">Title</span>
                        <input data-page-title required maxlength="255" placeholder="Article headline" class="h-12 rounded-xl border border-cms-navy/10 bg-white px-4 text-base font-semibold outline-none focus:border-cms-gold">
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold">URL slug</span>
                        <span class="flex h-11 min-w-0 items-center overflow-hidden rounded-xl border border-cms-navy/10 bg-white text-sm focus-within:border-cms-gold">
                            <span data-post-slug-prefix class="flex h-full shrink-0 items-center border-r border-cms-navy/8 bg-cms-canvas/60 px-3 text-cms-navy/45">/blog/</span>
                            <input data-page-slug required maxlength="255" pattern="[A-Za-z0-9_\-]+" class="h-full min-w-0 flex-1 px-3 outline-none">
                        </span>
                    </label>
                    <label class="grid gap-2">
                        <span class="flex items-baseline justify-between gap-3 text-sm font-semibold">Excerpt <span data-post-excerpt-count class="text-xs font-normal text-cms-navy/40">0 / 160</span></span>
                        <textarea data-post-excerpt maxlength="1000" rows="3" class="rounded-xl border border-cms-navy/10 bg-white px-4 py-3 text-sm outline-none focus:border-cms-gold"></textarea>
                        <span class="text-xs leading-5 text-cms-navy/45">Shown on article cards and as the search-result description. Around 120–160 characters reads best.</span>
                    </label>
                </section>

                <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="font-bold">Content blocks</h3>
                            <p class="mt-1 text-xs text-cms-navy/45">Build and arrange the article sections.</p>
                        </div>
                        <div class="flex gap-2">
                            <select data-block-type class="h-10 min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"></select>
                            <button data-add-block type="button" class="h-10 shrink-0 rounded-xl bg-cms-gold px-4 text-sm font-bold">Add block</button>
                        </div>
                    </div>
                    <div data-block-list class="mt-6 grid gap-4"></div>
                    <div data-block-empty class="mt-6 rounded-2xl border border-dashed border-cms-navy/15 px-5 py-10 text-center">
                        <p class="text-sm font-semibold">No content blocks yet</p>
                        <p class="mt-1 text-xs text-cms-navy/45">Start with a Rich Text block for the article body.</p>
                    </div>
                </section>
            </div>

            <aside class="grid content-start gap-5">
                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold">Publishing</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Status</span>
                            <select data-page-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="draft">Draft</option><option value="published">Published</option></select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Type</span>
                            <select data-post-type class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="blog">Blog</option><option value="news">News</option></select>
                        </label>
                    </div>
                    <label data-post-published-field class="grid gap-2">
                        <span class="text-xs font-semibold text-cms-navy/60">Publish date</span>
                        <input data-post-published-at type="datetime-local" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm">
                        <span class="text-xs leading-5 text-cms-navy/45">Leave empty to publish now. A future date schedules the article.</span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl bg-cms-canvas/60 p-3">
                        <input data-post-featured type="checkbox" class="mt-0.5 size-4 shrink-0 accent-cms-green">
                        <span class="grid gap-0.5"><span class="text-sm font-semibold">Featured article</span><span class="text-xs leading-5 text-cms-navy/45">The newest featured article heads the listing.</span></span>
                    </label>
                </section>

                <section class="grid gap-3 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold">Cover image</h3>
                    <div class="grid gap-3">
                        <span data-post-cover-preview class="grid aspect-video w-full place-items-center overflow-hidden rounded-xl border border-dashed border-cms-navy/15 bg-cms-canvas/60 text-xs text-cms-navy/35">No cover yet</span>
                        <input data-post-cover type="hidden">
                        <div class="flex items-center gap-3">
                            <span data-post-cover-copy class="min-w-0 flex-1 truncate text-xs text-cms-navy/45">No media selected</span>
                            <button data-post-clear-cover type="button" class="hidden h-9 shrink-0 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold">Remove</button>
                            <button data-post-choose-cover type="button" class="h-9 shrink-0 rounded-lg bg-cms-navy px-3 text-xs font-bold text-white">Choose</button>
                        </div>
                    </div>
                    <p class="text-xs leading-5 text-cms-navy/45">Shown on the article and in listings. 16:9 works best.</p>
                </section>

                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold">Taxonomy</h3>
                    <div class="grid gap-2">
                        <span class="text-xs font-semibold text-cms-navy/60">Categories</span>
                        <select data-post-categories data-choice-list multiple class="sr-only" tabindex="-1" aria-hidden="true"></select>
                    </div>
                    <div class="grid gap-2">
                        <span class="text-xs font-semibold text-cms-navy/60">Tags</span>
                        <select data-post-tags data-choice-list multiple class="sr-only" tabindex="-1" aria-hidden="true"></select>
                    </div>
                    <a href="{{ route('cms.taxonomies.index') }}" class="text-xs font-semibold text-cms-green">Manage categories & tags →</a>
                </section>

                @if ($seoPanel['visible'])
                    <section data-post-seo data-site-name="{{ $seoPanel['siteName'] }}" data-site-url="{{ url('/') }}" data-editable="{{ $seoPanel['editable'] ? 'true' : 'false' }}" class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                        <div class="flex items-baseline justify-between gap-3">
                            <h3 class="font-bold">Search & social</h3>
                            <span data-post-seo-score class="text-xs font-semibold text-cms-navy/40"></span>
                        </div>

                        <div class="grid gap-1 rounded-xl border border-cms-navy/8 bg-white p-3" aria-label="Search result preview">
                            <span data-post-seo-preview-url class="truncate text-[0.7rem] text-cms-navy/50"></span>
                            <span data-post-seo-preview-title class="line-clamp-2 text-[0.95rem] leading-5 font-semibold text-blue-800"></span>
                            <span data-post-seo-preview-description class="line-clamp-3 text-xs leading-5 text-cms-navy/60"></span>
                        </div>

                        <label class="grid gap-2">
                            <span class="flex items-baseline justify-between gap-3 text-xs font-semibold text-cms-navy/60">Meta title <span data-post-seo-title-count class="font-normal text-cms-navy/40">0 / 60</span></span>
                            <input data-post-seo-title maxlength="70" placeholder="Uses the post title" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65">
                        </label>
                        <label class="grid gap-2">
                            <span class="flex items-baseline justify-between gap-3 text-xs font-semibold text-cms-navy/60">Meta description <span data-post-seo-description-count class="font-normal text-cms-navy/40">0 / 160</span></span>
                            <textarea data-post-seo-description maxlength="170" rows="3" placeholder="Uses the excerpt" class="rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65"></textarea>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Focus keyword</span>
                            <input data-post-seo-keyword maxlength="255" placeholder="e.g. check-in event" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65">
                        </label>

                        <div class="grid gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Share image</span>
                            <div class="flex items-center gap-3">
                                <span data-post-seo-image-preview class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-dashed border-cms-navy/15 bg-cms-canvas/60 text-[0.65rem] text-cms-navy/35">Cover</span>
                                <input data-post-seo-image type="hidden">
                                <span data-post-seo-image-copy class="min-w-0 flex-1 truncate text-xs text-cms-navy/45">Uses the cover image</span>
                                <button data-post-seo-clear-image type="button" class="hidden h-9 shrink-0 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold">Remove</button>
                                <button data-post-seo-choose-image type="button" class="h-9 shrink-0 rounded-lg bg-cms-navy px-3 text-xs font-bold text-white disabled:opacity-40">Choose</button>
                            </div>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl bg-cms-canvas/60 p-3">
                            <input data-post-seo-noindex type="checkbox" class="mt-0.5 size-4 shrink-0 accent-cms-green">
                            <span class="grid gap-0.5"><span class="text-sm font-semibold">Hide from search engines</span><span class="text-xs leading-5 text-cms-navy/45">Adds noindex and leaves the article out of the sitemap.</span></span>
                        </label>

                        <p class="text-xs leading-5 text-cms-navy/45">
                            @if ($seoPanel['editable'])
                                Saved together with the post.
                            @else
                                You can view SEO but not change it.
                            @endif
                            Canonical URL, Open Graph text and structured data live in <a href="{{ route('cms.seo.index') }}" class="font-semibold text-cms-green">SEO settings →</a>
                        </p>
                    </section>
                @endif

                <x-cms.translation-panel />
            </aside>
        </form>
    </section>
</x-cms.app-layout>
