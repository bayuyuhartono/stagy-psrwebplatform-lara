{{-- Consecutive feature groups alternate side and background, keyed off their position among feature groups on the page. --}}
@php($isEven = $position % 2 === 0)
<x-public.container class="py-4">
    <section @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif @class([
        'flex flex-wrap items-center gap-10 rounded-[1.75rem] p-[clamp(1.5rem,4vw,3rem)]',
        'flex-row bg-public-mist' => $isEven,
        'flex-row-reverse bg-public-paper' => ! $isEven,
    ])>
        <div class="flex min-w-0 flex-[1_1_21.25rem] flex-col gap-4">
            @if (filled($data['label'] ?? null))
                <x-public.eyebrow>{{ sprintf('%02d', $position + 1) }} · {{ $data['label'] }}</x-public.eyebrow>
            @endif
            <x-public.heading size="subsection" :text="$data['heading'] ?? ''" />
            @if (filled($data['description'] ?? null))
                <p class="text-sm leading-[1.8] text-public-muted">{{ $data['description'] }}</p>
            @endif
            <ul class="flex flex-col gap-3">
                @foreach ($data['points'] ?? [] as $point)
                    <x-public.check-item class="leading-[1.7]">{{ $point }}</x-public.check-item>
                @endforeach
            </ul>
        </div>
        <div class="flex min-h-80 min-w-0 flex-[1.3_1_25rem] flex-col gap-2.5 rounded-[1.25rem] border border-public-line bg-public-paper p-3.5">
            <div class="flex gap-1.5" aria-hidden="true">
                <span class="size-2.5 rounded-full bg-public-line"></span><span class="size-2.5 rounded-full bg-public-line"></span><span class="size-2.5 rounded-full bg-public-line"></span>
            </div>
            @if ($blockMedia)
                <img src="{{ $blockMedia->url }}" @if ($blockMedia->srcset) srcset="{{ $blockMedia->srcset }}" sizes="(min-width: 1024px) 40rem, 100vw" @endif alt="{{ $blockMedia->alt_text ?? '' }}" loading="lazy" decoding="async" class="w-full flex-1 rounded-xl object-cover">
            @else
                <div class="flex flex-1 flex-col items-center justify-center gap-2.5 rounded-xl bg-public-mint p-6 text-center text-public-deep">
                    <x-public.icon name="monitor" class="size-8.5 text-public-accent" stroke-width="1.6" />
                    <span class="text-[0.8125rem] tracking-[0.03em]">{{ $data['media_placeholder'] ?? $data['heading'] ?? '' }}</span>
                    <span class="text-xs text-public-muted">{{ __('theme::site.screenshot_placeholder') }}</span>
                </div>
            @endif
        </div>
    </section>
</x-public.container>
