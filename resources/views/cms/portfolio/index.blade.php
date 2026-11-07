<x-cms.app-layout title="Portfolio" section="portfolio">
    <section data-portfolio-list data-can-update="{{ auth()->user()->hasPermission('portfolio.update') ? 'true' : 'false' }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div><p class="text-sm text-cms-navy/55">Manage client work and case studies.</p><h2 class="mt-1 text-2xl font-bold">Portfolio</h2></div>
            @can('create', App\Models\Portfolio::class)<a href="{{ route('cms.portfolio.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-cms-gold px-5 text-sm font-bold">+ New project</a>@endcan
        </div>
        <div class="rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
            <div class="grid gap-3 border-b border-cms-navy/8 p-5 sm:grid-cols-[1fr_10rem_10rem]"><input data-portfolio-search type="search" placeholder="Search projects or clients…" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm outline-none focus:border-cms-gold"><select data-portfolio-status class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">All statuses</option><option value="published">Published</option><option value="draft">Draft</option></select><select data-portfolio-featured class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">All projects</option><option value="1">Featured</option><option value="0">Not featured</option></select></div>
            <div data-portfolio-error class="hidden border-b border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>
            <div data-portfolio-grid class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3"><p class="text-sm text-cms-navy/45">Loading portfolio…</p></div>
            <div class="flex items-center justify-between border-t border-cms-navy/8 p-5"><p data-portfolio-summary class="text-xs text-cms-navy/45"></p><div class="flex gap-2"><button data-portfolio-previous type="button" class="h-9 rounded-lg border bg-white px-3 text-xs disabled:opacity-35">Previous</button><button data-portfolio-next type="button" class="h-9 rounded-lg border bg-white px-3 text-xs disabled:opacity-35">Next</button></div></div>
        </div>
    </section>
</x-cms.app-layout>
