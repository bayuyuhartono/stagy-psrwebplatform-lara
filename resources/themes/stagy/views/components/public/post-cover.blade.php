@props(['post', 'sizes' => '(min-width: 1024px) 24rem, 100vw', 'label' => null])

{{-- The post's cover image, or a placeholder tinted by its first category so listings stay varied. --}}
@php
    $cover = $post->coverImage;
    $palettes = [
        ['bg-public-accent', 'text-public-mint'],
        ['bg-public-mint', 'text-public-deep'],
        ['bg-public-ink', 'text-public-on-ink'],
        ['bg-public-sage', 'text-public-deep'],
    ];
    $categorySlug = $post->categories->first()?->slug ?? $post->slug;
    [$background, $foreground] = $palettes[crc32((string) $categorySlug) % count($palettes)];
@endphp

<div {{ $attributes->class(['relative overflow-hidden', $background => ! $cover]) }}>
    @if ($cover)
        <img src="{{ $cover->url }}" @if ($cover->srcset) srcset="{{ $cover->srcset }}" sizes="{{ $sizes }}" @endif alt="{{ $cover->alt_text ?? '' }}" loading="lazy" decoding="async" class="size-full object-cover">
    @else
        <span class="absolute inset-0 grid place-items-center px-6 text-center text-xs tracking-[0.06em] {{ $foreground }}" aria-hidden="true">{{ $post->categories->first()?->name ?? __('theme::site.blog.cover') }}</span>
    @endif
    {{ $slot }}
</div>
