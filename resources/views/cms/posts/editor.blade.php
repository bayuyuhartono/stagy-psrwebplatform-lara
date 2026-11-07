<x-cms.app-layout :title="isset($post) ? 'Edit post' : 'New post'" section="posts">
    <section data-page-editor data-resource="posts" data-page-id="{{ $post->id ?? '' }}" data-index-url="{{ route('cms.posts.index') }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><a href="{{ route('cms.posts.index') }}" class="text-xs font-semibold text-cms-green">← Back to posts</a><h2 class="mt-2 text-2xl font-bold tracking-tight text-cms-navy">{{ isset($post) ? 'Edit post' : 'Create a new post' }}</h2><p class="mt-1 text-sm text-cms-navy/50">Write a blog article or company news update.</p></div>
            <div class="flex gap-2">@isset($post) @can('delete', $post)<button data-delete-page type="button" class="h-11 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Delete</button>@endcan @endisset<button data-save-page type="submit" form="page-editor-form" class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Save post</button></div>
        </div>
        <div data-page-notice class="hidden rounded-xl border p-4 text-sm"></div>
        <form id="page-editor-form" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid min-w-0 gap-6">
                <section class="grid gap-5 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <label class="grid gap-2"><span class="text-sm font-semibold">Title</span><input data-page-title required maxlength="255" class="h-12 rounded-xl border border-cms-navy/10 bg-white px-4 text-base font-semibold outline-none focus:border-cms-gold"></label>
                    <label class="grid gap-2"><span class="text-sm font-semibold">URL slug</span><input data-page-slug required maxlength="255" pattern="[A-Za-z0-9_-]+" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm outline-none focus:border-cms-gold"></label>
                    <label class="grid gap-2"><span class="text-sm font-semibold">Excerpt</span><textarea data-post-excerpt maxlength="1000" class="min-h-24 rounded-xl border border-cms-navy/10 bg-white px-4 py-3 text-sm outline-none focus:border-cms-gold"></textarea></label>
                </section>
                <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><h3 class="font-bold">Content blocks</h3><p class="mt-1 text-xs text-cms-navy/45">Build and arrange the article sections.</p></div><div class="flex gap-2"><select data-block-type class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"></select><button data-add-block type="button" class="h-10 rounded-xl bg-cms-gold px-4 text-sm font-bold">Add block</button></div></div>
                    <div data-block-list class="mt-6 grid gap-4"></div><div data-block-empty class="mt-6 rounded-2xl border border-dashed border-cms-navy/15 px-5 py-10 text-center"><p class="text-sm font-semibold">No content blocks yet</p><p class="mt-1 text-xs text-cms-navy/45">Add a Rich Text, Hero, Image, CTA, or Feature Grid block.</p></div>
                </section>
            </div>
            <aside class="grid content-start gap-5">
                <x-cms.translation-panel />
                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm"><h3 class="font-bold">Publishing</h3><label class="grid gap-2"><span class="text-xs font-semibold text-cms-navy/60">Content type</span><select data-post-type class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="blog">Blog</option><option value="news">News</option></select></label><label class="grid gap-2"><span class="text-xs font-semibold text-cms-navy/60">Status</span><select data-page-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold"><option value="draft">Draft</option><option value="published">Published</option></select></label></section>
                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm"><h3 class="font-bold">Taxonomy</h3><label class="grid gap-2"><span class="text-xs font-semibold text-cms-navy/60">Categories</span><select data-post-categories multiple class="min-h-28 rounded-xl border border-cms-navy/10 bg-white p-2 text-sm"></select></label><label class="grid gap-2"><span class="text-xs font-semibold text-cms-navy/60">Tags</span><select data-post-tags multiple class="min-h-28 rounded-xl border border-cms-navy/10 bg-white p-2 text-sm"></select></label><p class="text-xs leading-5 text-cms-navy/45">Hold Ctrl or Command to select multiple items.</p></section>
            </aside>
        </form>
    </section>
</x-cms.app-layout>
