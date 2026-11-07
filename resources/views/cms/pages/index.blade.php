<x-cms.app-layout title="Pages" section="pages">
    <section data-pages-list data-can-update="{{ auth()->user()->hasPermission('pages.update') ? 'true' : 'false' }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Create and publish the core pages of your website.</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-cms-navy">Website pages</h2>
            </div>
            @can('create', App\Models\Page::class)
                <a href="{{ route('cms.pages.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-cms-gold px-5 text-sm font-bold text-cms-navy hover:bg-cms-gold/85">
                    <span class="text-lg leading-none">+</span> New page
                </a>
            @endcan
        </div>

        <div class="rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
            <div class="grid gap-3 border-b border-cms-navy/8 p-4 sm:grid-cols-[1fr_11rem] sm:p-5">
                <label class="relative block">
                    <span class="sr-only">Search pages</span>
                    <svg class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-cms-navy/35" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                    <input data-pages-search type="search" placeholder="Search title or slug…" class="h-11 w-full rounded-xl border border-cms-navy/10 bg-white px-4 pl-10 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                </label>
                <select data-pages-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm text-cms-navy outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                    <option value="">All statuses</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <div data-pages-error class="m-4 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-3xl text-left">
                    <thead class="text-[0.68rem] font-semibold tracking-[0.14em] text-cms-navy/40 uppercase">
                        <tr>
                            <th class="px-5 py-3">Page</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Updated</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody data-pages-rows class="divide-y divide-cms-navy/6">
                        @for ($index = 0; $index < 4; $index++)
                            <tr class="animate-pulse"><td colspan="4" class="px-5 py-5"><div class="h-4 rounded bg-cms-navy/6"></div></td></tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-cms-navy/8 p-4 sm:flex-row sm:px-5">
                <p data-pages-summary class="text-xs text-cms-navy/45">Loading pages…</p>
                <div class="flex gap-2">
                    <button data-pages-previous type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold text-cms-navy disabled:cursor-not-allowed disabled:opacity-35">Previous</button>
                    <button data-pages-next type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold text-cms-navy disabled:cursor-not-allowed disabled:opacity-35">Next</button>
                </div>
            </div>
        </div>
    </section>
</x-cms.app-layout>
