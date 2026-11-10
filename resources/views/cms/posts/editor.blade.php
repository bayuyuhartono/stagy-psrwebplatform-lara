<x-cms.app-layout :title="isset($post) ? 'Edit post' : 'New post'" section="posts">
    {{--
        Laid out in the order an article is written: headline, body, summary, search
        settings. The sidebar (sticky on wide screens) holds publishing, the
        pre-publish checklist, the cover image and taxonomy.
    --}}
    <section data-page-editor data-resource="posts" data-page-id="{{ $post->id ?? '' }}" data-index-url="{{ route('cms.posts.index') }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div class="min-w-0">
                <a data-editor-back href="{{ route('cms.posts.index') }}" class="text-xs font-semibold text-cms-green">← Back to posts</a>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-cms-navy">{{ isset($post) ? 'Edit post' : 'Create a new post' }}</h2>
                <p class="mt-1 text-sm text-cms-navy/50">Write a blog article or company news update.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span data-unsaved-indicator class="hidden text-xs font-semibold text-cms-gold">● Unsaved changes</span>
                <a data-post-view href="#" target="_blank" rel="noopener" class="hidden h-11 items-center rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold text-cms-navy">View on site ↗</a>
                @isset($post)
                    @can('delete', $post)
                        <button data-delete-page type="button" class="h-11 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Delete</button>
                    @endcan
                @endisset
                <button data-save-page type="submit" form="page-editor-form" class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white disabled:opacity-60">Save post</button>
            </div>
        </div>

        <div data-page-notice class="hidden rounded-xl border p-4 text-sm"></div>

        <form id="page-editor-form" class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid min-w-0 content-start gap-6">
                <section class="grid gap-3 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold">Title</span>
                        <input data-page-title required maxlength="255" placeholder="Article headline" class="h-13 rounded-xl border border-cms-navy/10 bg-white px-4 text-lg font-semibold outline-none focus:border-cms-gold">
                    </label>
                    {{-- Filled from the title; a quiet one-line field that only looks editable on hover or focus. --}}
                    <label class="flex min-w-0 items-center gap-2 text-xs text-cms-navy/50">
                        <span class="shrink-0 font-semibold">URL</span>
                        <span class="flex min-w-0 flex-1 items-center rounded-lg border border-transparent px-2 py-1 hover:border-cms-navy/10 focus-within:border-cms-gold focus-within:bg-white">
                            <span data-post-slug-prefix class="shrink-0">/blog/</span>
                            <input data-page-slug required maxlength="255" pattern="[A-Za-z0-9_\-]+" aria-label="URL slug" class="min-w-0 flex-1 bg-transparent font-semibold text-cms-navy outline-none">
                            <span class="shrink-0 text-cms-navy/35" aria-hidden="true">✎</span>
                        </span>
                    </label>
                </section>

                <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <div>
                        <h3 class="font-bold">Article</h3>
                        <p class="mt-1 text-xs text-cms-navy/45">Write the body in the text editor. Add other blocks (image, call to action…) below it when needed.</p>
                    </div>
                    <div data-block-list class="mt-5 grid gap-4"></div>
                    <div data-block-empty class="mt-5 rounded-2xl border border-dashed border-cms-navy/15 px-5 py-10 text-center">
                        <p class="text-sm font-semibold">No content blocks yet</p>
                        <p class="mt-1 text-xs text-cms-navy/45">Add a Rich Text block for the article body.</p>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-dashed border-cms-navy/10 pt-4">
                        <span class="text-xs font-semibold text-cms-navy/50">Add block</span>
                        <select data-block-type aria-label="Block type" class="h-10 min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"></select>
                        <button data-add-block type="button" class="h-10 shrink-0 rounded-xl bg-cms-gold px-4 text-sm font-bold">+ Add</button>
                    </div>
                </section>

                <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <label class="grid gap-2">
                        <span class="flex items-baseline justify-between gap-3 text-sm font-semibold">Excerpt <span data-post-excerpt-count class="text-xs font-normal text-cms-navy/40">0 / 160</span></span>
                        <textarea data-post-excerpt maxlength="1000" rows="3" placeholder="One or two sentences on what the reader gets from this article." class="rounded-xl border border-cms-navy/10 bg-white px-4 py-3 text-sm outline-none focus:border-cms-gold"></textarea>
                        <span class="text-xs leading-5 text-cms-navy/45">Easiest to write once the article is done. Shown on article cards, under the headline, and as the search description when Meta description is empty. 120–160 characters reads best.</span>
                    </label>
                </section>

                @if ($seoPanel['visible'])
                    <details data-post-seo open data-site-name="{{ $seoPanel['siteName'] }}" data-site-url="{{ url('/') }}" data-editable="{{ $seoPanel['editable'] ? 'true' : 'false' }}" class="group rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5 sm:px-6 [&::-webkit-details-marker]:hidden">
                            <span class="grid gap-0.5">
                                <span class="font-bold">Search & social</span>
                                <span class="text-xs text-cms-navy/45">How the post appears on Google and when shared, and what to improve.</span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span data-post-seo-score class="rounded-full bg-cms-canvas px-3 py-1 text-xs font-bold text-cms-navy/50">SEO —</span>
                                <span class="text-cms-navy/40 transition group-open:rotate-180" aria-hidden="true">▾</span>
                            </span>
                        </summary>

                        <div class="grid gap-5 border-t border-cms-navy/8 p-5 sm:p-6">
                            <div class="grid gap-1 rounded-xl border border-cms-navy/8 bg-white p-4" aria-label="Search result preview">
                                <span class="text-[0.65rem] font-semibold tracking-wide text-cms-navy/35 uppercase">Google preview</span>
                                <span data-post-seo-preview-url class="truncate text-xs text-cms-navy/50"></span>
                                <span data-post-seo-preview-title class="line-clamp-1 text-lg leading-6 text-blue-800"></span>
                                <span data-post-seo-preview-description class="line-clamp-2 text-sm leading-5 text-cms-navy/65"></span>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <label class="grid content-start gap-2">
                                    <span class="text-xs font-semibold text-cms-navy/60">Focus keyword</span>
                                    <input data-post-seo-keyword maxlength="255" placeholder="e.g. buku tamu digital" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65">
                                    <span class="text-xs leading-5 text-cms-navy/45">The phrase people type into Google that this article should answer.</span>
                                </label>
                                <label class="grid content-start gap-2">
                                    <span class="flex items-baseline justify-between gap-3 text-xs font-semibold text-cms-navy/60">Meta title <span data-post-seo-title-count class="font-normal text-cms-navy/40">0 / 60</span></span>
                                    <input data-post-seo-title maxlength="70" placeholder="Uses the post title" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65">
                                </label>
                                <label class="grid gap-2 lg:col-span-2">
                                    <span class="flex items-baseline justify-between gap-3 text-xs font-semibold text-cms-navy/60">Meta description <span data-post-seo-description-count class="font-normal text-cms-navy/40">0 / 160</span></span>
                                    <textarea data-post-seo-description maxlength="170" rows="2" placeholder="Uses the excerpt" class="rounded-xl border border-cms-navy/10 bg-white px-3 py-2.5 text-sm outline-none focus:border-cms-gold disabled:bg-cms-canvas/65"></textarea>
                                </label>
                                <div class="grid content-start gap-2">
                                    <span class="text-xs font-semibold text-cms-navy/60">Share image</span>
                                    <div class="flex items-center gap-3">
                                        <span data-post-seo-image-preview class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-dashed border-cms-navy/15 bg-cms-canvas/60 text-[0.65rem] text-cms-navy/35">Cover</span>
                                        <input data-post-seo-image type="hidden">
                                        <span data-post-seo-image-copy class="min-w-0 flex-1 truncate text-xs text-cms-navy/45">Uses the cover image</span>
                                        <button data-post-seo-clear-image type="button" class="hidden h-9 shrink-0 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-bold">Remove</button>
                                        <button data-post-seo-choose-image type="button" class="h-9 shrink-0 rounded-lg bg-cms-navy px-3 text-xs font-bold text-white disabled:opacity-40">Choose</button>
                                    </div>
                                </div>
                                <label class="flex items-start gap-3 self-end rounded-xl bg-cms-canvas/60 p-3">
                                    <input data-post-seo-noindex type="checkbox" class="mt-0.5 size-4 shrink-0 accent-cms-green">
                                    <span class="grid gap-0.5"><span class="text-sm font-semibold">Hide from search engines</span><span class="text-xs leading-5 text-cms-navy/45">Adds noindex and leaves the article out of the sitemap.</span></span>
                                </label>
                            </div>

                            <div class="grid gap-3">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <h4 class="text-sm font-bold">SEO analysis</h4>
                                    <span class="text-xs text-cms-navy/45">Updates as you write · 80+ good · 50–79 needs work · under 50 poor</span>
                                </div>
                                <div data-post-seo-checks class="grid gap-4 md:grid-cols-3" aria-live="polite"></div>
                            </div>

                            <p class="text-xs leading-5 text-cms-navy/45">
                                @if ($seoPanel['editable'])
                                    Saved together with the post.
                                @else
                                    You can view SEO but not change it.
                                @endif
                                Canonical URL, Open Graph text and structured data live in <a href="{{ route('cms.seo.index') }}" class="font-semibold text-cms-green">SEO settings →</a>
                            </p>
                        </div>
                    </details>
                @endif
            </div>

            <aside class="grid min-w-0 grid-cols-[minmax(0,1fr)] content-start gap-5 xl:sticky xl:top-24 xl:max-h-[calc(100vh-7rem)] xl:overflow-y-auto xl:overflow-x-hidden xl:pb-2">
                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold">Publish</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid min-w-0 gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Status</span>
                            <select data-page-status class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="draft">Draft</option><option value="published">Published</option></select>
                        </label>
                        <label class="grid min-w-0 gap-2">
                            <span class="text-xs font-semibold text-cms-navy/60">Type</span>
                            <select data-post-type class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="blog">Blog</option><option value="news">News</option></select>
                        </label>
                    </div>
                    <label data-post-published-field class="grid min-w-0 gap-2">
                        <span class="text-xs font-semibold text-cms-navy/60">Publish date</span>
                        <input data-post-published-at type="datetime-local" class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm">
                        <span class="text-xs leading-5 text-cms-navy/45">Leave empty to publish now. A future date schedules the article.</span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl bg-cms-canvas/60 p-3">
                        <input data-post-featured type="checkbox" class="mt-0.5 size-4 shrink-0 accent-cms-green">
                        <span class="grid gap-0.5"><span class="text-sm font-semibold">Featured article</span><span class="text-xs leading-5 text-cms-navy/45">The newest featured article heads the listing.</span></span>
                    </label>

                    <div class="grid gap-2 border-t border-cms-navy/8 pt-4">
                        <span class="text-xs font-semibold text-cms-navy/60">Before you publish</span>
                        <ul data-publish-checklist class="grid gap-1.5 text-xs"></ul>
                        <span class="text-[0.7rem] leading-4 text-cms-navy/40">Reminders only; nothing here blocks publishing.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button data-post-preview type="button" class="h-11 rounded-xl border border-cms-navy/10 bg-white text-sm font-bold text-cms-navy">Preview ↗</button>
                        <button data-save-page type="submit" form="page-editor-form" class="h-11 rounded-xl bg-cms-green text-sm font-bold text-white disabled:opacity-60">Save post</button>
                    </div>
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
                    <p class="text-xs leading-5 text-cms-navy/45">Shown on the article, in listings and when shared. 16:9 works best.</p>
                </section>

                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold">Categories & tags</h3>
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

                <x-cms.translation-panel />
            </aside>
        </form>
    </section>
</x-cms.app-layout>
