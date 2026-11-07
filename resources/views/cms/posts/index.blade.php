<x-cms.app-layout title="Posts & News" section="posts">
    <section data-posts-list data-can-update="{{ auth()->user()->hasPermission('posts.update') ? 'true' : 'false' }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-sm text-cms-navy/55">Publish blog articles and company news.</p><h2 class="mt-1 text-2xl font-bold tracking-tight text-cms-navy">Posts & News</h2></div>
            @can('create', App\Models\Post::class)<a href="{{ route('cms.posts.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-cms-gold px-5 text-sm font-bold text-cms-navy"><span class="text-lg">+</span> New post</a>@endcan
        </div>
        <div class="rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
            <div class="grid gap-3 border-b border-cms-navy/8 p-4 sm:grid-cols-[1fr_10rem_10rem] sm:p-5">
                <input data-posts-search type="search" placeholder="Search title or excerpt…" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm outline-none focus:border-cms-gold">
                <select data-posts-type class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">All types</option><option value="blog">Blog</option><option value="news">News</option></select>
                <select data-posts-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">All statuses</option><option value="published">Published</option><option value="draft">Draft</option></select>
            </div>
            <div data-posts-error class="m-4 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>
            <div class="overflow-x-auto"><table class="w-full min-w-3xl text-left"><thead class="text-[0.68rem] font-semibold tracking-[0.14em] text-cms-navy/40 uppercase"><tr><th class="px-5 py-3">Post</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody data-posts-rows class="divide-y divide-cms-navy/6"><tr><td colspan="4" class="px-5 py-10 text-center text-sm text-cms-navy/45">Loading posts…</td></tr></tbody></table></div>
            <div class="flex items-center justify-between gap-3 border-t border-cms-navy/8 p-4 px-5"><p data-posts-summary class="text-xs text-cms-navy/45"></p><div class="flex gap-2"><button data-posts-previous type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold disabled:opacity-35">Previous</button><button data-posts-next type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold disabled:opacity-35">Next</button></div></div>
        </div>
    </section>
</x-cms.app-layout>
