@props(['href', 'variant' => 'primary', 'size' => 'md'])

@inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')

{{-- Renders nothing when the link resolves to nothing (e.g. "whatsapp" with no number configured). --}}
@php($resolved = $links->resolve($href))
@if ($resolved !== null)
    <a href="{{ $resolved }}" @if ($links->opensNewTab($resolved)) target="_blank" rel="noopener" @endif {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-xl tracking-[0.04em] transition focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-public-accent',
        'min-h-12 px-5.5 text-sm' => $size === 'md',
        'min-h-11 px-4.5 text-[0.8125rem]' => $size === 'sm',
        'bg-public-ink text-white hover:bg-public-deep' => $variant === 'primary',
        'border-[1.5px] border-public-line bg-public-paper text-public-ink hover:border-public-accent' => $variant === 'outline',
        'bg-public-paper text-public-ink hover:bg-public-mint' => $variant === 'light',
        'bg-public-accent text-white hover:bg-public-deep' => $variant === 'accent',
        'border-[1.5px] border-white/30 text-white hover:border-white' => $variant === 'ghost',
    ]) }}>{{ $slot }}</a>
@endif
