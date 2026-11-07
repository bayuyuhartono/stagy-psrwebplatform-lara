@if ($blockMedia)
    <x-public.container as="figure" width="media" class="py-12">
        <img
            src="{{ $blockMedia->url }}"
            @if ($blockMedia->srcset) srcset="{{ $blockMedia->srcset }}" sizes="(min-width: 1024px) 60rem, 100vw" @endif
            @if ($width = $blockMedia->metadata['width'] ?? null) width="{{ $width }}" @endif
            @if ($height = $blockMedia->metadata['height'] ?? null) height="{{ $height }}" @endif
            alt="{{ $data['alt'] ?? $blockMedia->alt_text ?? '' }}"
            loading="lazy"
            decoding="async"
            class="w-full rounded-[1.75rem] bg-public-mint object-cover">
        @if (filled($data['caption'] ?? null))
            <figcaption class="mt-3 text-[0.8125rem] text-public-muted">{{ $data['caption'] }}</figcaption>
        @endif
    </x-public.container>
@endif
