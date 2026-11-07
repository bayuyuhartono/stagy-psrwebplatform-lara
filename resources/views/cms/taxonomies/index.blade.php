<x-cms.app-layout title="Categories & Tags" section="taxonomies">
    <section data-taxonomy-manager data-can-create="{{ auth()->user()->hasPermission('taxonomies.create') ? 'true' : 'false' }}" data-can-update="{{ auth()->user()->hasPermission('taxonomies.update') ? 'true' : 'false' }}" data-can-delete="{{ auth()->user()->hasPermission('taxonomies.delete') ? 'true' : 'false' }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Organize posts and portfolio projects for easier discovery.</p>
                <h2 class="mt-1 text-2xl font-bold">Categories & Tags</h2>
            </div>
            @if (auth()->user()->hasPermission('taxonomies.create'))
                <button data-taxonomy-new type="button" class="h-11 rounded-xl bg-cms-gold px-5 text-sm font-bold text-cms-navy">+ New category</button>
            @endif
        </div>

        <div data-taxonomy-notice class="hidden rounded-xl border p-4 text-sm"></div>

        <div class="flex gap-2 border-b border-cms-navy/10">
            <button data-taxonomy-tab="categories" type="button" class="border-b-2 border-cms-green px-4 py-3 text-sm font-bold text-cms-green">Categories</button>
            <button data-taxonomy-tab="tags" type="button" class="border-b-2 border-transparent px-4 py-3 text-sm font-bold text-cms-navy/45">Tags</button>
        </div>

        <div class="grid gap-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row">
                <input data-taxonomy-search type="search" placeholder="Filter categories by name or slug…" class="h-11 w-full rounded-xl border border-cms-navy/10 bg-white px-4 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15 sm:max-w-md">
                <p data-taxonomy-context class="self-center text-xs font-semibold text-cms-navy/40">Showing categories</p>
            </div>
            <div class="overflow-x-auto rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
                <table class="w-full min-w-[42rem] text-left">
                    <thead class="border-b border-cms-navy/8 text-xs text-cms-navy/45">
                        <tr><th class="px-5 py-4">Name</th><th class="px-5 py-4">Used by posts</th><th class="px-5 py-4">Used by portfolio</th><th class="px-5 py-4 text-right">Action</th></tr>
                    </thead>
                    <tbody data-taxonomy-rows></tbody>
                </table>
                <div class="flex items-center justify-between gap-4 border-t border-cms-navy/8 p-4">
                    <p data-taxonomy-summary class="text-xs text-cms-navy/45"></p>
                    <div class="flex gap-2"><button data-taxonomy-previous type="button" class="h-8 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs disabled:opacity-35">Previous</button><button data-taxonomy-next type="button" class="h-8 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs disabled:opacity-35">Next</button></div>
                </div>
            </div>
        </div>

        <div data-taxonomy-dialog class="fixed inset-0 z-60 hidden items-center justify-center bg-cms-navy/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="taxonomy-dialog-title">
            <form data-taxonomy-form class="w-full max-w-lg rounded-2xl bg-cms-canvas p-6 shadow-2xl">
                <input data-taxonomy-id type="hidden">
                <div class="flex items-center justify-between gap-4">
                    <h3 id="taxonomy-dialog-title" data-taxonomy-form-title class="text-xl font-bold text-cms-navy">New category</h3>
                    <button data-taxonomy-cancel type="button" class="grid size-9 place-items-center rounded-xl border border-cms-navy/10 bg-white text-cms-navy" aria-label="Close">×</button>
                </div>
                <div class="mt-6 grid gap-4">
                    <label class="grid gap-2"><span class="text-xs font-semibold">Name</span><input data-taxonomy-name required maxlength="255" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15"></label>
                    <label class="grid gap-2"><span class="text-xs font-semibold">Slug</span><input data-taxonomy-slug required maxlength="255" pattern="[A-Za-z0-9_-]+" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 font-mono text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15"><span class="text-xs text-cms-navy/40">Used in public URLs and content filters.</span></label>
                </div>
                <div class="mt-6 flex justify-between gap-3">
                    <button data-taxonomy-delete type="button" class="hidden h-11 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Delete</button>
                    <button data-taxonomy-save class="ml-auto h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Save</button>
                </div>
            </form>
        </div>
    </section>
</x-cms.app-layout>
