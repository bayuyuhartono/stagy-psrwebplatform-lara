@if ($blockMedia)
    <figure class="mx-auto max-w-240 px-4 py-12 sm:px-6">
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
    </figure>
@endif
