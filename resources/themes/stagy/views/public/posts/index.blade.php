{{--
    Blog / news listing. The CMS page whose slug matches the listing ("blog")
    supplies the heading (its first block) and closing blocks (the rest), plus SEO.
--}}
@inject('readingTime', 'App\Features\Posts\Services\ReadingTimeEstimator')

@php
    $archive = $archive ?? null;
    $introBlocks = collect($introPage?->blocks ?? []);
    $activeCategory = ($archive['taxonomy'] ?? null) === 'category' ? $archive['term']->slug : null;
    $listingRoute = 'website.'.$type->value;
    $gridPosts = $posts->getCollection()->reject(fn ($post) => $post->is($featured));
    $locale = app()->getLocale();
    $postUrl = fn ($post) => localized_route($listingRoute.'.show', $post->slug);
    $postDate = fn ($post) => $post->published_at?->locale($locale)->translatedFormat('j M Y');
@endphp

<x-public.layout
    :page="$introPage"
    :settings="$settings"
    :menu="$menu"
    :meta-title="$archive ? $archive['term']->name.' — '.__('theme::site.blog.eyebrow') : ($introPage?->title ?? __('theme::site.blog.eyebrow'))"
    :canonical="$archive ? localized_route($listingRoute.'.'.$archive['taxonomy'], $archive['term']->slug) : localized_route($listingRoute.'.index')">

    @if ($introBlocks->isNotEmpty())
        @include('public.partials.blocks', ['blocks' => [$introBlocks->first()]])
    @else
        @include('public.blocks.page_header', ['data' => ['eyebrow' => __('theme::site.blog.eyebrow'), 'heading' => __('theme::site.blog.heading'), 'intro' => __('theme::site.blog.intro')], 'blockMedia' => null, 'position' => 0])
    @endif

    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('theme::site.blog.categories') }}" class="mx-auto -mt-4 flex max-w-290 flex-wrap gap-2 px-4 pb-10 sm:px-6">
            @foreach ([['label' => __('theme::site.blog.all'), 'url' => localized_route($listingRoute.'.index'), 'active' => $archive === null], ...$categories->map(fn ($category) => ['label' => $category->name, 'url' => localized_route($listingRoute.'.category', $category->slug), 'active' => $category->slug === $activeCategory])] as $tab)
                <a href="{{ $tab['url'] }}" @if ($tab['active']) aria-current="page" @endif @class([
                    'inline-flex min-h-11 items-center rounded-full border-[1.5px] px-4.5 text-[0.8125rem] tracking-[0.04em] transition',
                    'border-public-ink bg-public-ink text-white' => $tab['active'],
                    'border-public-line bg-public-paper text-public-ink hover:border-public-accent' => ! $tab['active'],
                ])>{{ $tab['label'] }}</a>
            @endforeach
        </nav>
    @endif

    @if ($featured)
        <section class="mx-auto max-w-290 px-4 pb-6 sm:px-6">
            <a href="{{ $postUrl($featured) }}" class="group flex flex-wrap overflow-hidden rounded-[1.75rem] border border-public-line bg-public-paper transition hover:border-public-accent">
                <x-public.post-cover :post="$featured" sizes="(min-width: 1024px) 36rem, 100vw" class="min-h-64 flex-[1.2_1_22rem]">
                    <span class="absolute top-5 left-5 rounded-full bg-public-paper px-3 py-1.5 text-[0.6875rem] tracking-[0.06em] text-public-ink">{{ __('theme::site.blog.latest') }}</span>
                </x-public.post-cover>
                <div class="flex min-w-0 flex-[1_1_20rem] flex-col justify-center gap-4 p-[clamp(1.5rem,4vw,2.75rem)]">
                    <span class="text-xs tracking-[0.06em] text-public-accent">{{ collect([$featured->categories->first()?->name, $postDate($featured)])->filter()->join(' · ') }}</span>
                    <h2 class="text-[clamp(1.375rem,2.6vw,1.875rem)] leading-[1.3] font-normal">{{ $featured->title }}</h2>
                    @if (filled($featured->excerpt))
                        <p class="text-sm leading-[1.8] text-public-muted">{{ $featured->excerpt }}</p>
                    @endif
                    <span class="text-sm tracking-[0.04em] underline underline-offset-4 group-hover:text-public-accent">{{ __('theme::site.blog.read_article') }} → <span class="text-xs text-public-muted no-underline">· {{ __('theme::site.blog.reading_time', ['count' => $readingTime->minutes($featured)]) }}</span></span>
                </div>
            </a>
        </section>
    @endif

    <section class="mx-auto max-w-290 px-4 pt-4 pb-14 sm:px-6">
        @if ($gridPosts->isEmpty() && ! $featured)
            <p class="rounded-2xl bg-public-mist p-8 text-center text-sm text-public-muted">{{ __('theme::site.blog.empty') }}</p>
        @else
            <div class="grid grid-cols-[repeat(auto-fill,minmax(17.5rem,1fr))] gap-5">
                @foreach ($gridPosts as $post)
                    <a href="{{ $postUrl($post) }}" class="group flex flex-col overflow-hidden rounded-[1.375rem] border border-public-line bg-public-paper transition hover:border-public-accent">
                        <x-public.post-cover :post="$post" class="aspect-[16/10]" />
                        <div class="flex flex-1 flex-col gap-3 p-6">
                            <span class="text-xs tracking-[0.06em] text-public-accent">{{ collect([$post->categories->first()?->name, $postDate($post)])->filter()->join(' · ') }}</span>
                            <h3 class="text-[1.0625rem] leading-[1.45] font-normal group-hover:text-public-accent">{{ $post->title }}</h3>
                            @if (filled($post->excerpt))
                                <p class="line-clamp-3 text-[0.8125rem] leading-[1.75] text-public-muted">{{ $post->excerpt }}</p>
                            @endif
                            <span class="mt-auto text-xs text-public-muted">{{ __('theme::site.blog.reading_time', ['count' => $readingTime->minutes($post)]) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($posts->hasPages())
            <nav aria-label="{{ __('theme::site.blog.pagination') }}" class="mt-12 flex flex-wrap items-center justify-center gap-2">
                @php($pageLink = 'grid size-11 place-items-center rounded-xl border border-public-line text-sm transition hover:border-public-accent')
                @if ($posts->onFirstPage())
                    <span class="{{ $pageLink }} opacity-40" aria-hidden="true"><x-public.icon name="arrow-left" class="size-4" /></span>
                @else
                    <a href="{{ $posts->previousPageUrl() }}" rel="prev" aria-label="{{ __('theme::site.blog.previous_page') }}" class="{{ $pageLink }}"><x-public.icon name="arrow-left" class="size-4" /></a>
                @endif
                @foreach (range(1, $posts->lastPage()) as $number)
                    <a href="{{ $posts->url($number) }}" @if ($number === $posts->currentPage()) aria-current="page" @endif @class([$pageLink, 'border-public-ink bg-public-ink text-white' => $number === $posts->currentPage()])>{{ $number }}</a>
                @endforeach
                @if ($posts->hasMorePages())
                    <a href="{{ $posts->nextPageUrl() }}" rel="next" aria-label="{{ __('theme::site.blog.next_page') }}" class="{{ $pageLink }}"><x-public.icon name="arrow-right" class="size-4" /></a>
                @else
                    <span class="{{ $pageLink }} opacity-40" aria-hidden="true"><x-public.icon name="arrow-right" class="size-4" /></span>
                @endif
            </nav>
        @endif
    </section>

    @include('public.partials.blocks', ['blocks' => $introBlocks->slice(1)->values()->all()])
</x-public.layout>
