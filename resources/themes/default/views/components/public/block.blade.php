@props(['block', 'media' => null])

@php($type = $block['type'] ?? null)
@php($data = $block['data'] ?? [])
@php($blockMedia = ($media ?? collect())->get($data['media_id'] ?? ''))

@if ($type === 'rich_text' && filled($data['content'] ?? null))
    <section class="public-reveal mx-auto max-w-4xl px-5 py-16 sm:px-8 lg:py-24"><p class="whitespace-pre-line text-lg leading-8 text-public-ink/65 sm:text-xl">{{ $data['content'] }}</p></section>
@elseif ($type === 'feature_grid' && filled($data['items'] ?? []))
    <section class="mx-auto grid max-w-7xl gap-px bg-public-ink/10 px-5 py-16 sm:grid-cols-2 sm:px-8 lg:grid-cols-3 lg:px-10 lg:py-24">@foreach ($data['items'] as $item)<article class="public-reveal bg-public-paper p-7 lg:p-10"><span class="text-xs font-bold text-public-surface">0{{ $loop->iteration }}</span><h3 class="mt-12 text-2xl font-bold tracking-tight">{{ $item['title'] ?? '' }}</h3><p class="mt-4 leading-7 text-public-ink/55">{{ $item['description'] ?? '' }}</p></article>@endforeach</section>
@elseif ($type === 'image' && $blockMedia)
    <figure class="public-reveal mx-auto max-w-5xl px-5 py-16 sm:px-8 lg:py-24">
        <img
            src="{{ $blockMedia->url }}"
            @if ($blockMedia->srcset) srcset="{{ $blockMedia->srcset }}" sizes="(min-width: 1024px) 64rem, 100vw" @endif
            @if ($width = $blockMedia->metadata['width'] ?? null) width="{{ $width }}" @endif
            @if ($height = $blockMedia->metadata['height'] ?? null) height="{{ $height }}" @endif
            alt="{{ $data['alt'] ?? $blockMedia->alt_text ?? '' }}"
            loading="lazy"
            decoding="async"
            class="w-full rounded-[1.75rem] bg-public-surface object-cover">
        @if (filled($data['caption'] ?? null))
            <figcaption class="mt-4 text-sm text-public-ink/55">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@elseif ($type === 'call_to_action')
    <section class="public-reveal mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-10 lg:py-24"><div class="grid gap-8 rounded-[2rem] bg-public-surface p-8 text-white sm:p-12 lg:grid-cols-[1fr_auto] lg:items-end lg:p-16"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-public-accent">Next step</p><h2 class="mt-5 max-w-3xl text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">{{ $data['heading'] ?? '' }}</h2><p class="mt-5 max-w-2xl text-white/65">{{ $data['body'] ?? '' }}</p></div><a href="{{ $data['url'] ?? '#contact' }}" class="inline-flex rounded-full bg-public-accent px-6 py-3 text-sm font-bold text-public-ink">{{ $data['label'] ?? 'Get started' }} ↗</a></div></section>
@endif
