@props(['post', 'excerpt' => true])

@inject('readingTime', 'App\Features\Posts\Services\ReadingTimeEstimator')

{{-- An article teaser card for listings and "related articles". --}}
@php($listingRoute = 'website.'.$post->type->value)
<a href="{{ localized_route($listingRoute.'.show', $post->slug) }}" {{ $attributes->class('group flex flex-col overflow-hidden rounded-[1.375rem] border border-public-line bg-public-paper transition hover:border-public-accent') }}>
    <x-public.post-cover :post="$post" class="aspect-[16/10]" />
    <div class="flex flex-1 flex-col gap-3 p-6">
        <span class="text-xs tracking-[0.06em] text-public-accent">{{ collect([$post->categories->first()?->name, $post->published_at?->locale(app()->getLocale())->translatedFormat('j M Y')])->filter()->join(' · ') }}</span>
        <h3 class="text-[1.0625rem] leading-[1.45] font-normal group-hover:text-public-accent">{{ $post->title }}</h3>
        @if ($excerpt)
            @if (filled($post->excerpt))
                <p class="line-clamp-3 text-[0.8125rem] leading-[1.75] text-public-muted">{{ $post->excerpt }}</p>
            @endif
            <span class="mt-auto text-xs text-public-muted">{{ __('theme::site.blog.reading_time', ['count' => $readingTime->minutes($post)]) }}</span>
        @endif
    </div>
</a>
