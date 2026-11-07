{{-- Native <details> keeps the accordion keyboard- and screen-reader-friendly without script; the first answer starts open. --}}
<x-public.container as="section" class="flex flex-wrap items-start gap-14 pt-18 pb-22">
    @if (filled($data['heading'] ?? null) || filled($data['eyebrow'] ?? null) || filled($data['intro'] ?? null))
        <div class="flex min-w-0 flex-[1_1_18.75rem] flex-col gap-5 lg:sticky lg:top-28">
            @if (filled($data['eyebrow'] ?? null))
                <x-public.eyebrow>{{ $data['eyebrow'] }}</x-public.eyebrow>
            @endif
            @if (filled($data['heading'] ?? null))
                <x-public.heading :level="1" size="title" :text="$data['heading']" />
            @endif
            @if (filled($data['intro'] ?? null))
                <p class="leading-[1.8] text-public-muted">{{ $data['intro'] }}</p>
            @endif
            @if ($data['show_whatsapp'] ?? false)
                <x-public.button href="whatsapp" variant="accent" class="self-start">
                    <x-public.icon name="whatsapp" class="size-4.5" />{{ __('theme::site.chat_whatsapp') }}
                </x-public.button>
            @endif
        </div>
    @endif
    <div class="flex min-w-0 flex-[1.6_1_27.5rem] flex-col gap-3">
        @foreach ($data['items'] ?? [] as $item)
            <details @if ($loop->first) open @endif class="group rounded-2xl border border-public-line bg-public-paper open:border-public-accent">
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[0.9375rem] leading-[1.5] [&::-webkit-details-marker]:hidden">
                    <span>{{ $item['question'] ?? '' }}</span>
                    <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg bg-public-mint text-base text-public-ink group-open:bg-public-ink group-open:text-white">
                        <span class="group-open:hidden">+</span><span class="hidden group-open:inline">−</span>
                    </span>
                </summary>
                <p class="px-5 pb-5 text-sm leading-[1.85] whitespace-pre-line text-public-muted">{{ $item['answer'] ?? '' }}</p>
            </details>
        @endforeach
    </div>
</x-public.container>
