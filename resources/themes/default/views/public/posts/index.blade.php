@php($archive = $archive ?? null)
@php($archiveLabel = $archive ? ($archive['taxonomy'] === 'category' ? 'Category' : 'Tag').': '.$archive['term']->name : null)
<x-public.layout
    :settings="$settings"
    :menu="$menu"
    :meta-title="$archiveLabel ? $archiveLabel.' — '.($type->value === 'blog' ? 'Insights' : 'News') : ($type->value === 'blog' ? 'Insights — Pesoros' : 'News — Pesoros')"
    :meta-description="$archiveLabel ? 'Posts filed under '.$archive['term']->name.'.' : ($type->value === 'blog' ? 'Ideas and practical perspectives from the Pesoros team.' : 'Latest announcements and updates from Pesoros.')"
    :canonical="$archive ? localized_route('website.'.$type->value.'.'.$archive['taxonomy'], $archive['term']->slug) : localized_route('website.'.$type->value.'.index')">
    <section class="relative overflow-hidden bg-public-ink pb-24 pt-40 text-white lg:pb-32 lg:pt-52">
        <div class="absolute -right-24 top-24 size-[30rem] rounded-full border border-white/10"></div>
        <div class="absolute right-20 top-48 size-36 rounded-full bg-public-accent sm:right-40 sm:size-52"></div>
        <div class="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <p class="public-reveal text-xs font-bold uppercase tracking-[0.22em] text-public-accent">{{ $archiveLabel ?? ($type->value === 'blog' ? 'Ideas & perspectives' : 'Studio updates') }}</p>
            <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_26rem] lg:items-end">
                <h1 class="public-reveal text-6xl font-semibold leading-[0.88] tracking-[-0.06em] sm:text-8xl lg:text-9xl">{{ $archive ? $archive['term']->name : ($type->value === 'blog' ? 'Thinking out loud.' : 'What’s happening.') }}</h1>
                <p class="public-reveal text-lg leading-8 text-white/55">{{ $archive ? 'Every '.$type->value.' post filed under '.$archive['term']->name.'.' : ($type->value === 'blog' ? 'Notes on strategy, design, technology, and building digital work that matters.' : 'Company milestones, project launches, and stories from inside the studio.') }}</p>
            </div>
            @if ($archive)
                <a href="{{ localized_route('website.'.$type->value.'.index') }}" class="public-reveal mt-8 inline-flex rounded-full border border-white/25 px-5 py-2.5 text-sm font-bold transition hover:border-public-accent hover:text-public-accent">← All {{ $type->value }} posts</a>
            @endif
        </div>
    </section>

    <section class="bg-public-paper">
        <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-32">
            <div class="grid gap-x-7 gap-y-16 md:grid-cols-2">
                @forelse ($posts as $post)
                    <article class="public-reveal group {{ $loop->first ? 'md:col-span-2' : '' }}">
                        <a href="{{ localized_route('website.'.$type->value.'.show', $post->slug) }}" class="block">
                            <div class="relative overflow-hidden rounded-[1.75rem] bg-public-surface {{ $loop->first ? 'aspect-[16/8]' : 'aspect-[4/3]' }}">
                                @if ($post->seo?->ogImage)
                                    <img src="{{ Storage::disk($post->seo->ogImage->disk)->url($post->seo->ogImage->path) }}" alt="{{ $post->seo->ogImage->alt_text ?? $post->title }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                @else
                                    <div class="absolute inset-0 bg-linear-to-br from-public-surface via-public-ink to-public-accent/65"></div>
                                    <span class="absolute bottom-7 left-7 text-7xl font-semibold text-white/10 sm:text-9xl">{{ str($post->title)->substr(0, 1) }}</span>
                                @endif
                                <span class="absolute right-5 top-5 grid size-12 place-items-center rounded-full bg-public-paper opacity-0 transition group-hover:opacity-100">↗</span>
                            </div>
                            <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-bold uppercase tracking-wide text-public-ink/40">
                                <span class="text-public-surface">{{ $post->categories->first()?->name ?? str($type->value)->headline() }}</span>
                                <span>·</span>
                                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time>
                                @if ($post->author)
                                    <span>· By {{ $post->author->name }}</span>
                                @endif
                            </div>
                            <h2 class="mt-3 max-w-4xl text-3xl font-semibold tracking-[-0.04em] sm:text-4xl {{ $loop->first ? 'lg:text-6xl' : '' }}">{{ $post->title }}</h2>
                            <p class="mt-4 max-w-3xl leading-7 text-public-ink/50">{{ $post->excerpt }}</p>
                        </a>
                    </article>
                @empty
                    <div class="col-span-full py-24 text-center text-sm text-public-ink/45">Articles are being prepared.</div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-16">{{ $posts->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </section>
</x-public.layout>
