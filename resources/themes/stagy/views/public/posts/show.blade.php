@inject('readingTime', 'App\Features\Posts\Services\ReadingTimeEstimator')
@inject('blockMediaResolver', 'App\Features\ContentBlocks\Services\BlockMediaResolver')

@php
    $listingRoute = 'website.'.$type->value;
    $locale = app()->getLocale();
    $category = $post->categories->first();
    $articleUrl = localized_route($listingRoute.'.show', $post->slug);
    $authorName = $post->author?->name ?? ($settings->get('general', [])['site_name'] ?? 'Stagy');
    $blockMedia = $blockMediaResolver->resolve($post->blocks);
    $shareText = rawurlencode($post->title.' '.$articleUrl);
@endphp

<x-public.layout :page="$post" :settings="$settings" :menu="$menu" :meta-title="$post->title" :meta-description="$post->excerpt" :canonical="$articleUrl">
    <article>
        <header class="mx-auto flex max-w-225 flex-col gap-5 px-4 pt-14 sm:px-6">
            <nav aria-label="{{ __('theme::site.blog.breadcrumb') }}" class="flex flex-wrap items-center gap-2 text-xs tracking-[0.04em] text-public-muted">
                <a href="{{ localized_route($listingRoute.'.index') }}" class="py-2 text-public-ink underline underline-offset-4 hover:text-public-accent">{{ __('theme::site.blog.eyebrow') }}</a>
                @if ($category)
                    <span aria-hidden="true">/</span>
                    <a href="{{ localized_route($listingRoute.'.category', $category->slug) }}" class="py-2 hover:text-public-accent">{{ $category->name }}</a>
                @endif
            </nav>
            <h1 class="text-[clamp(1.75rem,4vw,2.75rem)] leading-[1.2] font-normal">{{ $post->title }}</h1>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[0.8125rem] text-public-muted">
                <span class="inline-flex items-center gap-2.5 text-public-ink">
                    <span class="grid size-8 place-items-center rounded-full bg-public-accent text-xs text-white" aria-hidden="true">{{ str($authorName)->substr(0, 1)->upper() }}</span>{{ $authorName }}
                </span>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->locale($locale)->translatedFormat('j M Y') }}</time>
                @endif
                <span>{{ __('theme::site.blog.reading_time', ['count' => $readingTime->minutes($post)]) }}</span>
            </div>
        </header>

        <div class="mx-auto max-w-290 px-4 pt-8 pb-4 sm:px-6">
            <x-public.post-cover :post="$post" sizes="(min-width: 1200px) 72rem, 100vw" class="aspect-[16/9] rounded-[1.75rem]" />
        </div>

        @if (filled($post->excerpt))
            <p class="mx-auto max-w-180 px-4 pt-8 text-lg leading-[1.8] text-public-muted sm:px-6">{{ $post->excerpt }}</p>
        @endif

        <div class="pt-2 pb-6">
            @include('public.partials.blocks', ['blocks' => $post->blocks ?? []])
        </div>

        <div class="mx-auto flex max-w-180 flex-wrap items-center gap-3 border-t border-public-line px-4 pt-6 pb-16 sm:px-6">
            <span class="mr-2 text-xs tracking-[0.08em] text-public-muted">{{ __('theme::site.blog.share') }}</span>
            <a href="https://wa.me/?text={{ $shareText }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.blog.share_whatsapp') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="whatsapp" class="size-4.5" /></a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($articleUrl) }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.blog.share_linkedin') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="linkedin" class="size-4.5" /></a>
            <button type="button" data-copy-link="{{ $articleUrl }}" data-copied-label="{{ __('theme::site.blog.link_copied') }}" aria-label="{{ __('theme::site.blog.copy_link') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="link" class="size-4.5" /></button>
            <span data-copy-link-status role="status" aria-live="polite" class="text-xs text-public-accent"></span>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="bg-public-mist px-4 py-20 sm:px-6">
            <div class="mx-auto flex max-w-290 flex-col gap-10">
                <x-public.section-heading :heading="__('theme::site.blog.related')" :link="['label' => __('theme::site.blog.all_articles'), 'url' => localized_route($listingRoute.'.index')]" />
                <div class="grid grid-cols-[repeat(auto-fill,minmax(17.5rem,1fr))] gap-5">
                    @foreach ($related as $relatedPost)
                        <a href="{{ localized_route($listingRoute.'.show', $relatedPost->slug) }}" class="group flex flex-col overflow-hidden rounded-[1.375rem] border border-public-line bg-public-paper transition hover:border-public-accent">
                            <x-public.post-cover :post="$relatedPost" class="aspect-[16/10]" />
                            <div class="flex flex-col gap-3 p-6">
                                <span class="text-xs tracking-[0.06em] text-public-accent">{{ collect([$relatedPost->categories->first()?->name, $relatedPost->published_at?->locale($locale)->translatedFormat('j M Y')])->filter()->join(' · ') }}</span>
                                <h3 class="text-[1.0625rem] leading-[1.45] font-normal group-hover:text-public-accent">{{ $relatedPost->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-public.layout>
