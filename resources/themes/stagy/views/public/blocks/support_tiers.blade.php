@inject('catalog', 'App\Features\Pricing\Services\PricingCatalog')

@php($plans = $catalog->for()['plans']->filter(fn ($plan) => filled($plan->support_summary)))

@if ($plans->isNotEmpty())
    <section class="px-4 pb-22 sm:px-6">
        <div class="mx-auto flex max-w-290 flex-wrap items-center gap-x-14 gap-y-8 rounded-[1.75rem] bg-public-mist p-[clamp(1.5rem,4vw,3rem)]">
            <div class="flex min-w-0 flex-[1_1_18.75rem] flex-col gap-4">
                @if (filled($data['eyebrow'] ?? null))
                    <span class="text-xs tracking-[0.08em] text-public-accent">{{ $data['eyebrow'] }}</span>
                @endif
                <h2 class="text-[clamp(1.375rem,2.6vw,1.875rem)] leading-[1.25] font-normal"><x-public.highlight :text="$data['heading'] ?? ''" /></h2>
                @if (filled($data['link']['url'] ?? null))
                    @inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')
                    @if ($href = $links->resolve($data['link']['url']))
                        <a href="{{ $href }}" class="inline-flex items-center gap-2 self-start py-2.5 text-sm tracking-[0.04em] underline underline-offset-4 hover:text-public-accent">{{ $data['link']['label'] ?? '' }} <x-public.icon name="arrow-right" class="size-4" /></a>
                    @endif
                @endif
            </div>
            <ul class="flex min-w-0 flex-[1.4_1_25rem] flex-col gap-3">
                @foreach ($plans as $plan)
                    <li class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 rounded-2xl border border-public-line bg-public-paper px-5 py-4">
                        <span class="text-base">{{ $plan->name }}</span>
                        <span class="text-[0.8125rem] text-public-muted">{{ $plan->support_summary }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
