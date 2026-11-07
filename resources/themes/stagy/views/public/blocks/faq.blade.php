{{-- Native <details> keeps the accordion keyboard- and screen-reader-friendly without script; the first answer starts open. --}}
<section class="mx-auto flex max-w-290 flex-wrap items-start gap-14 px-4 pt-18 pb-22 sm:px-6">
    @if (filled($data['heading'] ?? null) || filled($data['eyebrow'] ?? null) || filled($data['intro'] ?? null))
        <div class="flex min-w-0 flex-[1_1_18.75rem] flex-col gap-5 lg:sticky lg:top-28">
            @if (filled($data['eyebrow'] ?? null))
                <span class="text-xs tracking-[0.08em] text-public-accent">{{ $data['eyebrow'] }}</span>
            @endif
            @if (filled($data['heading'] ?? null))
                <h1 class="text-[clamp(1.875rem,4vw,2.75rem)] leading-[1.18] font-normal"><x-public.highlight :text="$data['heading']" /></h1>
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
</section>
