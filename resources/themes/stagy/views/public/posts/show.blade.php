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

@pushOnce('fonts', 'reading-font')
    @fonts('instrument-sans')
@endPushOnce

<x-public.layout :page="$post" :settings="$settings" :menu="$menu" :meta-title="$post->title" :meta-description="$post->excerpt" :canonical="$articleUrl">
    <article>
        <x-public.container as="header" width="article" class="flex flex-col gap-5 pt-14">
            <nav aria-label="{{ __('theme::site.blog.breadcrumb') }}" class="flex flex-wrap items-center gap-2 text-xs tracking-[0.04em] text-public-muted">
                <a href="{{ localized_route($listingRoute.'.index') }}" class="py-2 text-public-ink underline underline-offset-4 hover:text-public-accent">{{ __('theme::site.blog.eyebrow') }}</a>
                @if ($category)
                    <span aria-hidden="true">/</span>
                    <a href="{{ localized_route($listingRoute.'.category', $category->slug) }}" class="py-2 hover:text-public-accent">{{ $category->name }}</a>
                @endif
            </nav>
            <x-public.heading :level="1" size="title">{{ $post->title }}</x-public.heading>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[0.8125rem] text-public-muted">
                <span class="inline-flex items-center gap-2.5 text-public-ink">
                    <span class="grid size-8 place-items-center rounded-full bg-public-accent text-xs text-white" aria-hidden="true">{{ str($authorName)->substr(0, 1)->upper() }}</span>{{ $authorName }}
                </span>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->locale($locale)->translatedFormat('j M Y') }}</time>
                @endif
                <span>{{ __('theme::site.blog.reading_time', ['count' => $readingTime->minutes($post)]) }}</span>
            </div>
        </x-public.container>

        <x-public.container class="pt-8 pb-4">
            <x-public.post-cover :post="$post" sizes="(min-width: 1200px) 72rem, 100vw" class="aspect-[16/9] rounded-[1.75rem]" />
        </x-public.container>

        @if (filled($post->excerpt))
            <x-public.container as="p" width="article" class="pt-8 font-public-body text-xl leading-[1.7] tracking-normal text-public-muted">{{ $post->excerpt }}</x-public.container>
        @endif

        <div class="pt-2 pb-6">
            @include('public.partials.blocks', ['blocks' => $post->blocks ?? []])
        </div>

        <x-public.container width="article" class="flex flex-wrap items-center gap-3 border-t border-public-line pt-6 pb-16">
            <span class="mr-2 text-xs tracking-[0.08em] text-public-muted">{{ __('theme::site.blog.share') }}</span>
            <a href="https://wa.me/?text={{ $shareText }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.blog.share_whatsapp') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="whatsapp" class="size-4.5" /></a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($articleUrl) }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.blog.share_linkedin') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="linkedin" class="size-4.5" /></a>
            <button type="button" data-copy-link="{{ $articleUrl }}" data-copied-label="{{ __('theme::site.blog.link_copied') }}" aria-label="{{ __('theme::site.blog.copy_link') }}" class="grid size-11 place-items-center rounded-xl border border-public-line transition hover:border-public-accent"><x-public.icon name="link" class="size-4.5" /></button>
            <span data-copy-link-status role="status" aria-live="polite" class="text-xs text-public-accent"></span>
        </x-public.container>
    </article>

    @if ($related->isNotEmpty())
        <section class="bg-public-mist py-20">
            <x-public.container class="flex flex-col gap-10">
                <x-public.section-heading :heading="__('theme::site.blog.related')" :link="['label' => __('theme::site.blog.all_articles'), 'url' => localized_route($listingRoute.'.index')]" />
                <div class="grid grid-cols-[repeat(auto-fill,minmax(17.5rem,1fr))] gap-5">
                    @foreach ($related as $relatedPost)
                        <x-public.post-card :post="$relatedPost" :excerpt="false" />
                    @endforeach
                </div>
            </x-public.container>
        </section>
    @endif
</x-public.layout>
