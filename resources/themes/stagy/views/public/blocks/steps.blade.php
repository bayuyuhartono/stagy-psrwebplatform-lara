@php($items = $data['items'] ?? [])
@if (($data['variant'] ?? 'compact') === 'detailed')
    <x-public.container as="section" class="pt-6 pb-22">
        <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :link="$data['link'] ?? null" />
        <ol @class(['grid grid-cols-[repeat(auto-fit,minmax(15rem,1fr))] gap-4', 'mt-10' => filled($data['heading'] ?? null) || filled($data['eyebrow'] ?? null)])>
            @foreach ($items as $item)
                {{-- The last step is the payoff, so it is set in navy. --}}
                @php($isLast = $loop->last && $loop->count > 1)
                <li @class([
                    'flex flex-col gap-4 rounded-[1.125rem] border px-6 py-6.5',
                    'border-public-line bg-public-paper text-public-ink' => ! $isLast,
                    'border-public-ink bg-public-ink text-white' => $isLast,
                ])>
                    <x-public.number-badge :number="$loop->iteration" :tone="$isLast ? 'accent' : 'ink'" />
                    <h2 class="text-lg leading-[1.35] font-normal">{{ $item['title'] ?? '' }}</h2>
                    @foreach (['you' => __('theme::site.steps_you'), 'us' => __('theme::site.steps_us')] as $key => $who)
                        @if (filled($item[$key] ?? null))
                            <div class="flex flex-col gap-1">
                                <x-public.eyebrow :tone="$isLast ? 'sage' : 'accent'">{{ $who }}</x-public.eyebrow>
                                <span @class(['text-[0.8125rem] leading-[1.75]', 'text-public-muted' => ! $isLast, 'text-public-on-ink' => $isLast])>{{ $item[$key] }}</span>
                            </div>
                        @endif
                    @endforeach
                </li>
            @endforeach
        </ol>
    </x-public.container>
@else
    <x-public.container as="section" class="flex flex-col gap-10 py-22">
        <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :link="$data['link'] ?? null" />
        <ol class="grid grid-cols-[repeat(auto-fit,minmax(13.75rem,1fr))] gap-6">
            @foreach ($items as $item)
                <li class="flex flex-col gap-3.5 border-t-2 border-public-ink pt-4.5">
                    <span class="text-2xl leading-none text-public-accent">{{ sprintf('%02d', $loop->iteration) }}</span>
                    <span class="text-sm leading-[1.7]">{{ $item['title'] ?? '' }}</span>
                </li>
            @endforeach
        </ol>
    </x-public.container>
@endif
