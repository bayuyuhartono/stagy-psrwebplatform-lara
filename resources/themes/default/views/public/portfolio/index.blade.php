<x-public.layout :settings="$settings" :menu="$menu" meta-title="Portfolio — Pesoros" meta-description="Selected digital products, platforms, and experiences created by Pesoros." :canonical="localized_route('website.portfolio.index')">
    <section class="relative overflow-hidden bg-public-ink pb-24 pt-40 text-white lg:pb-32 lg:pt-52"><div class="absolute -right-20 top-20 size-[32rem] rotate-12 rounded-[5rem] border border-white/10"></div><div class="absolute right-16 top-48 size-40 rounded-[3rem] bg-public-surface sm:right-36 sm:size-56"></div><div class="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10"><p class="public-reveal text-xs font-bold uppercase tracking-[0.22em] text-public-accent">Selected work</p><div class="mt-6 grid gap-10 lg:grid-cols-[1fr_26rem] lg:items-end"><h1 class="public-reveal text-6xl font-semibold leading-[0.88] tracking-[-0.06em] sm:text-8xl lg:text-9xl">Proof is in the work.</h1><p class="public-reveal text-lg leading-8 text-white/55">A selection of partnerships where strategy, design, and technology came together to create meaningful progress.</p></div></div></section>
    <section class="bg-public-paper">
        <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-32">
            <div class="grid gap-x-7 gap-y-16 md:grid-cols-2">
                @forelse ($projects as $project)
                    <a href="{{ localized_route('website.portfolio.show', $project->slug) }}" class="public-reveal group block {{ $loop->even ? 'md:mt-28' : '' }}">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-[1.75rem] bg-public-ink">
                            @if ($project->coverImage)
                                <img src="{{ Storage::disk($project->coverImage->disk)->url($project->coverImage->path) }}" alt="{{ $project->coverImage->alt_text ?? $project->title }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @else
                                <div class="absolute inset-0 bg-linear-to-br from-public-surface via-public-ink to-public-accent/70"></div>
                                <span class="absolute bottom-8 left-8 text-8xl font-semibold text-white/12">0{{ $projects->firstItem() + $loop->index }}</span>
                            @endif

                            @if ($project->is_featured)
                                <span class="absolute left-5 top-5 rounded-full bg-public-accent px-3 py-1.5 text-xs font-bold text-public-ink">Featured</span>
                            @endif

                            <span class="absolute right-5 top-5 grid size-12 place-items-center rounded-full bg-public-paper opacity-0 transition group-hover:opacity-100">↗</span>
                        </div>
                        <div class="mt-6 flex items-start justify-between gap-6">
                            <div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($project->categories as $category)
                                        <span class="text-xs font-bold uppercase tracking-wide text-public-surface">{{ $category->name }}</span>
                                    @endforeach
                                </div>
                                <h2 class="mt-2 text-3xl font-semibold tracking-[-0.04em]">{{ $project->title }}</h2>
                                <p class="mt-2 text-sm text-public-ink/40">{{ $project->client }} · {{ $project->project_date?->format('Y') }}</p>
                            </div>
                            <p class="hidden max-w-xs text-right text-sm leading-6 text-public-ink/45 lg:block">{{ str($project->excerpt)->limit(110) }}</p>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-24 text-center text-sm text-public-ink/45">Case studies are being prepared.</div>
                @endforelse
            </div>

            @if ($projects->hasPages())
                <div class="mt-16">{{ $projects->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </section>
</x-public.layout>
