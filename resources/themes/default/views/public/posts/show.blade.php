<x-public.layout :page="$post" :settings="$settings" :menu="$menu" :meta-title="$post->title" :meta-description="$post->excerpt" :canonical="localized_route('website.'.$type->value.'.show', $post->slug)">
    <article>
        <header class="bg-public-ink pb-16 pt-40 text-white lg:pb-24 lg:pt-52">
            <div class="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10">
                <a href="{{ localized_route('website.'.$type->value.'.index') }}" class="public-reveal text-xs font-bold uppercase tracking-[0.2em] text-public-accent">← {{ $type->value === 'blog' ? 'All insights' : 'All news' }}</a>
                <div class="public-reveal mt-10 flex flex-wrap items-center gap-3 text-xs font-bold uppercase tracking-wide text-white/45">
                    @foreach ($post->categories as $category)
                        <a href="{{ localized_route('website.'.$type->value.'.category', $category->slug) }}" class="text-public-accent transition hover:underline">{{ $category->name }}</a>
                    @endforeach
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d F Y') }}</time>
                    @if ($post->author)
                        <span>· {{ $post->author->name }}</span>
                    @endif
                </div>
                <h1 class="public-reveal mt-7 text-5xl font-semibold leading-[0.95] tracking-[-0.055em] sm:text-7xl lg:text-8xl">{{ $post->title }}</h1>
                <p class="public-reveal mt-8 max-w-3xl text-xl leading-8 text-white/55">{{ $post->excerpt }}</p>
            </div>
        </header>

        <section class="bg-public-ink pb-10">
            <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div class="public-reveal relative aspect-[16/9] overflow-hidden rounded-[1.75rem] bg-linear-to-br from-public-surface via-public-ink to-public-accent/65">
                    @if ($post->seo?->ogImage)
                        <img src="{{ Storage::disk($post->seo->ogImage->disk)->url($post->seo->ogImage->path) }}" alt="{{ $post->seo->ogImage->alt_text ?? $post->title }}" class="h-full w-full object-cover">
                    @else
                        <span class="absolute bottom-8 left-8 text-[clamp(6rem,20vw,16rem)] font-semibold leading-none text-white/10">{{ str($post->title)->substr(0, 1) }}</span>
                    @endif
                </div>
            </div>
        </section>

        <div class="bg-public-paper">
            @foreach ($post->blocks as $block)
                <x-public.block :block="$block" />
            @endforeach

            @if ($post->tags->isNotEmpty())
                <footer class="mx-auto max-w-4xl px-5 pb-20 sm:px-8 lg:pb-28">
                    <div class="public-reveal flex flex-wrap gap-2 border-t border-public-ink/10 pt-8">
                        @foreach ($post->tags as $tag)
                            <a href="{{ localized_route('website.'.$type->value.'.tag', $tag->slug) }}" class="rounded-full border border-public-ink/15 px-4 py-2 text-xs font-bold text-public-ink/55 transition hover:border-public-accent hover:text-public-ink">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                </footer>
            @endif
        </div>
    </article>

    <section class="bg-public-accent">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-8 px-5 py-20 sm:px-8 lg:flex-row lg:items-end lg:px-10 lg:py-24">
            <h2 class="public-reveal max-w-3xl text-4xl font-semibold tracking-[-0.04em] sm:text-6xl">More ideas are already taking shape.</h2>
            <a href="{{ localized_route('website.'.$type->value.'.index') }}" class="public-reveal rounded-full bg-public-ink px-6 py-3.5 text-sm font-bold text-white">Keep reading ↗</a>
        </div>
    </section>
</x-public.layout>
