@php($visual = $data['visual'] ?? 'ticket')
<x-public.container as="section" class="flex flex-wrap items-center gap-14 pt-18 pb-16">
    <div class="flex min-w-0 flex-[1_1_28.75rem] flex-col gap-6">
        @if (filled($data['badge'] ?? null))
            <x-public.badge tone="mint" class="self-start">{{ $data['badge'] }}</x-public.badge>
        @endif
        <x-public.heading :level="1" size="display" :text="$data['heading'] ?? ''" />
        @if (filled($data['subheading'] ?? null))
            <p class="max-w-140 leading-[1.8] text-public-muted">{{ $data['subheading'] }}</p>
        @endif
        @if (filled($data['primary_action']['url'] ?? null) || filled($data['secondary_action']['url'] ?? null))
            <div class="flex flex-wrap gap-3">
                @if (filled($data['primary_action']['url'] ?? null))
                    <x-public.button :href="$data['primary_action']['url']">{{ $data['primary_action']['label'] ?? '' }}</x-public.button>
                @endif
                @if (filled($data['secondary_action']['url'] ?? null))
                    <x-public.button :href="$data['secondary_action']['url']" variant="outline">{{ $data['secondary_action']['label'] ?? '' }}</x-public.button>
                @endif
            </div>
        @endif
        @if (filled($data['trust_points'] ?? []))
            <ul class="flex flex-wrap gap-x-6 gap-y-2.5 text-xs tracking-[0.04em] text-public-muted">
                @foreach ($data['trust_points'] as $point)
                    <li class="inline-flex items-center gap-2"><x-public.icon name="check" class="size-3.5 text-public-accent" stroke-width="2.6" />{{ $point }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($visual === 'media' && $blockMedia)
        <div class="flex min-w-0 flex-[0_1_25rem] justify-center">
            <img src="{{ $blockMedia->url }}" @if ($blockMedia->srcset) srcset="{{ $blockMedia->srcset }}" sizes="(min-width: 1024px) 25rem, 100vw" @endif alt="{{ $blockMedia->alt_text ?? '' }}" class="w-full rounded-[1.75rem] object-cover">
        </div>
    @elseif ($visual === 'ticket')
        <x-public.phone-mockup>
            <div class="bg-public-accent px-5 pt-5.5 pb-4.5 text-white">
                <div class="text-[0.6875rem] tracking-[0.08em] opacity-90">{{ $data['ticket_label'] ?? 'E-ticket · VIP' }}</div>
                @if (filled($data['ticket_event'] ?? null))
                    <div class="mt-2 text-[0.8125rem] leading-[1.45]">{{ $data['ticket_event'] }}</div>
                @endif
            </div>
            <div class="flex flex-col items-center gap-3 px-5 pt-4.5 pb-5">
                <x-public.ticket-qr class="size-37" />
                @if (filled($data['ticket_guest'] ?? null) || filled($data['ticket_detail'] ?? null))
                    <div class="text-center">
                        <div class="text-[0.8125rem]">{{ $data['ticket_guest'] ?? '' }}</div>
                        <div class="text-[0.6875rem] tracking-[0.04em] text-public-muted">{{ $data['ticket_detail'] ?? '' }}</div>
                    </div>
                @endif
                @if (filled($data['ticket_time'] ?? null) || filled($data['ticket_gate'] ?? null))
                    <div class="flex w-full justify-between border-t border-dashed border-public-line pt-2.5 text-[0.6875rem] text-public-muted">
                        <span>{{ $data['ticket_time'] ?? '' }}</span><span>{{ $data['ticket_gate'] ?? '' }}</span>
                    </div>
                @endif
            </div>
        </x-public.phone-mockup>
    @endif
</x-public.container>
