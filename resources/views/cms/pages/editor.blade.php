<x-cms.app-layout :title="isset($page) ? 'Edit page' : 'New page'" section="pages">
    <section
        data-page-editor
        data-page-id="{{ $page->id ?? '' }}"
        data-index-url="{{ route('cms.pages.index') }}"
        class="grid gap-6"
    >
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <a href="{{ route('cms.pages.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-cms-green hover:text-cms-navy">← Back to pages</a>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-cms-navy">{{ isset($page) ? 'Edit page' : 'Create a new page' }}</h2>
                <p class="mt-1 text-sm text-cms-navy/50">Build the page from reusable content sections.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @isset($page)
                    @can('delete', $page)
                        <button data-delete-page type="button" class="h-11 rounded-xl border border-red-200 bg-white/70 px-4 text-sm font-bold text-red-700 hover:bg-red-50">Delete</button>
                    @endcan
                @endisset
                <button data-save-page type="submit" form="page-editor-form" class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white shadow-lg shadow-cms-green/10 hover:bg-cms-navy">Save page</button>
            </div>
        </div>

        <div data-page-notice class="hidden rounded-xl border p-4 text-sm"></div>

        <form id="page-editor-form" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="grid min-w-0 gap-6">
                <section class="grid gap-5 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-cms-navy">Page title</span>
                        <input data-page-title type="text" required maxlength="255" placeholder="About our company" class="h-12 rounded-xl border border-cms-navy/10 bg-white px-4 text-base font-semibold outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-cms-navy">URL slug</span>
                        <div class="flex h-12 overflow-hidden rounded-xl border border-cms-navy/10 bg-white focus-within:border-cms-gold focus-within:ring-3 focus-within:ring-cms-gold/15">
                            <span class="flex items-center border-r border-cms-navy/8 bg-cms-canvas/65 px-3 text-sm text-cms-navy/40">/</span>
                            <input data-page-slug type="text" required maxlength="255" pattern="[A-Za-z0-9_-]+" placeholder="about-us" class="min-w-0 flex-1 px-3 text-sm outline-none">
                        </div>
                    </label>
                </section>

                <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="font-bold text-cms-navy">Content blocks</h3>
                            <p class="mt-1 text-xs text-cms-navy/45">Add sections and arrange them in page order.</p>
                        </div>
                        <div class="flex gap-2">
                            <select data-block-type class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold"></select>
                            <button data-add-block type="button" class="h-10 rounded-xl bg-cms-gold px-4 text-sm font-bold text-cms-navy hover:bg-cms-gold/85">Add block</button>
                        </div>
                    </div>
                    <div data-block-list class="mt-6 grid gap-4"></div>
                    <div data-block-empty class="mt-6 rounded-2xl border border-dashed border-cms-navy/15 px-5 py-10 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-xl bg-cms-canvas text-xl text-cms-green">+</span>
                        <p class="mt-3 text-sm font-semibold text-cms-navy">No content blocks yet</p>
                        <p class="mt-1 text-xs text-cms-navy/45">Choose a block type above to begin.</p>
                    </div>
                </section>
            </div>

            <aside class="grid content-start gap-5">
                <x-cms.translation-panel />
                <section class="grid gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <h3 class="font-bold text-cms-navy">Publishing</h3>
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold text-cms-navy/60">Status</span>
                        <select data-page-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold text-cms-navy outline-none focus:border-cms-gold">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </label>
                    <p class="rounded-xl bg-cms-canvas p-3 text-xs leading-5 text-cms-navy/55">Published pages become available through the public API immediately.</p>
                </section>

                <section class="rounded-2xl bg-cms-navy p-5 text-white shadow-sm">
                    <p class="text-xs font-semibold tracking-[0.14em] text-cms-gold uppercase">Editor tip</p>
                    <p class="mt-3 text-sm leading-6 text-white/65">Start with a Hero, add your main content, and finish with a Call to Action.</p>
                </section>
            </aside>
        </form>
    </section>
</x-cms.app-layout>
