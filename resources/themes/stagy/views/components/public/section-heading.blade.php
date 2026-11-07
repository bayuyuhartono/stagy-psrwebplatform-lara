@props(['eyebrow' => null, 'heading' => null, 'link' => null, 'level' => 2])

@inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')

{{-- Eyebrow + heading on the left, an optional "see more" link on the right. --}}
@php($href = filled($link['label'] ?? null) ? $links->resolve($link['url'] ?? null) : null)
@if (filled($eyebrow) || filled($heading) || $href)
    <div {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-10 gap-y-4') }}>
        <div class="flex max-w-160 flex-col gap-3">
            @if (filled($eyebrow))
                <x-public.eyebrow>{{ $eyebrow }}</x-public.eyebrow>
            @endif
            @if (filled($heading))
                <x-public.heading :level="$level" :text="$heading" />
            @endif
        </div>
        @if ($href)
            <a href="{{ $href }}" class="inline-flex items-center gap-2 py-2.5 text-sm tracking-[0.04em] text-public-ink underline underline-offset-4 hover:text-public-accent">{{ $link['label'] }} <x-public.icon name="arrow-right" class="size-4" /></a>
        @endif
    </div>
@endif
