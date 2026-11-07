<x-cms.app-layout title="Dashboard">
    <section data-dashboard class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ str(auth()->user()->name)->before(' ') }}.</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-cms-navy">Here’s what’s happening today.</h2>
            </div>
            <a href="#pages" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-cms-gold px-5 text-sm font-bold text-cms-navy hover:bg-cms-gold/85">
                <span class="text-lg leading-none">+</span> New page
            </a>
        </div>

        <div data-dashboard-error class="hidden rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Pages', 'pages', 'bg-cms-navy', 'text-white'],
                ['Posts', 'posts', 'bg-white/75', 'text-cms-navy'],
                ['Portfolio', 'portfolio', 'bg-white/75', 'text-cms-navy'],
                ['New messages', 'messages', 'bg-cms-green', 'text-white'],
            ] as [$label, $metric, $background, $text])
                <article class="rounded-2xl border border-cms-navy/8 {{ $background }} {{ $text }} p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-sm font-medium opacity-65">{{ $label }}</p>
                        <span class="size-2 rounded-full bg-cms-gold"></span>
                    </div>
                    <p data-metric="{{ $metric }}" class="mt-7 text-3xl font-bold tracking-tight">—</p>
                    <p class="mt-1 text-xs opacity-45">Total records</p>
                </article>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
            <section class="rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-cms-navy">Recent activity</h3>
                        <p class="mt-1 text-xs text-cms-navy/45">Latest changes across your website</p>
                    </div>
                    <span class="rounded-full bg-cms-canvas px-3 py-1 text-xs font-semibold text-cms-green">Live</span>
                </div>
                <div data-activity-list class="mt-6 grid gap-1">
                    @for ($index = 0; $index < 4; $index++)
                        <div class="flex animate-pulse items-center gap-3 border-b border-cms-navy/6 py-3 last:border-0">
                            <span class="size-9 rounded-xl bg-cms-navy/8"></span>
                            <span class="h-3 flex-1 rounded bg-cms-navy/8"></span>
                        </div>
                    @endfor
                </div>
            </section>

            <section class="rounded-2xl bg-cms-navy p-5 text-white shadow-sm sm:p-6">
                <p class="text-xs font-semibold tracking-[0.16em] text-cms-gold uppercase">Website health</p>
                <div class="mt-5 grid gap-4">
                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                        <span class="text-sm text-white/60">SEO coverage</span>
                        <strong data-metric="seo" class="text-lg">—</strong>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                        <span class="text-sm text-white/60">Media files</span>
                        <strong data-metric="media" class="text-lg">—</strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-white/60">System</span>
                        <strong class="flex items-center gap-2 text-sm"><span class="size-2 rounded-full bg-green-400"></span> Online</strong>
                    </div>
                </div>
            </section>
        </div>

        <div data-search-results class="fixed top-18 right-4 z-30 hidden max-h-[28rem] w-[min(24rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-cms-navy/10 bg-white p-2 shadow-2xl sm:right-6 lg:right-8"></div>
    </section>
</x-cms.app-layout>
