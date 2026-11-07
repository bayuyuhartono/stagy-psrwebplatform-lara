@props(['eyebrow' => null, 'heading' => null, 'link' => null, 'level' => 'h2'])

{{-- Eyebrow + heading on the left, an optional "see more" link on the right. --}}
@if (filled($eyebrow) || filled($heading) || filled($link['url'] ?? null))
    <div class="flex flex-wrap items-end justify-between gap-x-10 gap-y-4">
        <div class="flex max-w-160 flex-col gap-3">
            @if (filled($eyebrow))
                <span class="text-xs tracking-[0.08em] text-public-accent">{{ $eyebrow }}</span>
            @endif
            @if (filled($heading))
                <{{ $level }} class="text-[clamp(1.5rem,2.8vw,2.125rem)] leading-[1.25] font-normal"><x-public.highlight :text="$heading" /></{{ $level }}>
            @endif
        </div>
        @if (filled($link['url'] ?? null) && filled($link['label'] ?? null))
            @inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')
            @if ($href = $links->resolve($link['url']))
                <a href="{{ $href }}" class="inline-flex items-center gap-2 py-2.5 text-sm tracking-[0.04em] text-public-ink underline underline-offset-4 hover:text-public-accent">{{ $link['label'] }} <x-public.icon name="arrow-right" class="size-4" /></a>
            @endif
        @endif
    </div>
@endif
