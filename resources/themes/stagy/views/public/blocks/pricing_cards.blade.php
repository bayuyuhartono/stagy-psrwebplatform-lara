@inject('catalog', 'App\Features\Pricing\Services\PricingCatalog')
@inject('prices', 'App\Features\Pricing\Services\PriceFormatter')

@php($plans = $catalog->for()['plans'])
@php($locale = app()->getLocale())
@inject('themeSettings', 'App\Features\Themes\Services\ThemeSettings')
@php($demoUrl = localized_route('website.pages.show', $themeSettings->get('demo_page_slug', 'demo')))

@if ($plans->isNotEmpty())
    @if (($data['variant'] ?? 'full') === 'summary')
        <section class="mx-auto flex max-w-290 flex-col gap-10 px-4 py-22 sm:px-6">
            <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :link="$data['link'] ?? null" />
            <div class="flex flex-wrap items-stretch gap-4">
                @foreach ($plans as $plan)
                    <div @class([
                        'relative flex min-w-0 flex-[1_1_17.5rem] flex-col gap-2.5 rounded-[1.125rem] px-6.5 py-7',
                        'border-2 border-public-accent' => $plan->is_highlighted,
                        'border border-public-line' => ! $plan->is_highlighted,
                    ])>
                        @if ($plan->is_highlighted && filled($plan->highlight_label))
                            <span class="absolute -top-3.5 left-6 rounded-full bg-public-accent px-3 py-1.5 text-[0.6875rem] tracking-[0.06em] text-white">{{ $plan->highlight_label }}</span>
                        @endif
                        <span class="text-base">{{ $plan->name }}</span>
                        <span class="text-2xl leading-[1.2]">{{ $prices->format($plan->price, $locale) }}<span class="text-xs text-public-muted"> {{ __('theme::site.pricing.from') }}</span></span>
                        @if (filled($plan->tagline))
                            <span class="text-xs tracking-[0.03em] text-public-muted">{{ $plan->tagline }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            @if (filled($data['note'] ?? null))
                <p class="text-xs text-public-muted">{{ $data['note'] }}</p>
            @endif
        </section>
    @else
        <section class="mx-auto flex max-w-290 flex-col gap-8 px-4 pt-4 pb-16 sm:px-6">
            @if (filled($data['heading'] ?? null) || filled($data['eyebrow'] ?? null))
                <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :link="$data['link'] ?? null" />
            @endif
            @if (filled($data['intro'] ?? null))
                <p class="max-w-155 leading-[1.8] text-public-muted">{{ $data['intro'] }}</p>
            @endif
            <div class="flex flex-wrap items-stretch gap-4">
                @foreach ($plans as $plan)
                    <article @class([
                        'relative flex min-w-0 flex-[1_1_18.75rem] flex-col gap-5 rounded-[1.375rem] bg-public-paper p-7',
                        'border-2 border-public-accent' => $plan->is_highlighted,
                        'border border-public-line' => ! $plan->is_highlighted,
                    ])>
                        @if ($plan->is_highlighted && filled($plan->highlight_label))
                            <span class="absolute -top-3.5 left-7 rounded-full bg-public-accent px-3 py-1.5 text-[0.6875rem] tracking-[0.06em] text-white">{{ $plan->highlight_label }}</span>
                        @endif
                        <div class="flex flex-col gap-1.5">
                            <h2 class="text-xl font-normal">{{ $plan->name }}</h2>
                            @if (filled($plan->tagline))
                                <span class="text-xs tracking-[0.03em] text-public-muted">{{ $plan->tagline }}</span>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1.5 border-y border-public-mint py-4">
                            <span class="text-[clamp(1.625rem,3vw,2rem)] leading-none">{{ $prices->format($plan->price, $locale) }}</span>
                            <span class="text-xs leading-[1.6] text-public-muted">
                                {{ __('theme::site.pricing.starting_from') }} · {{ __('theme::site.pricing.per_event') }}@if ($plan->extra_guest_price !== null) · {{ __('theme::site.pricing.extra_guest', ['price' => $prices->format($plan->extra_guest_price, $locale)]) }}@endif
                            </span>
                        </div>
                        <ul class="flex flex-1 flex-col gap-3">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-3 text-sm leading-[1.6]">
                                    <span class="mt-0.5 grid size-5.5 shrink-0 place-items-center rounded-[0.4375rem] bg-public-sage text-public-deep"><x-public.icon name="check" class="size-3" stroke-width="3" /></span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if (filled($plan->cta_label))
                            <x-public.button :href="$plan->cta_url ?: $demoUrl" :variant="$plan->is_highlighted ? 'accent' : 'primary'" class="w-full">{{ $plan->cta_label }}</x-public.button>
                        @endif
                    </article>
                @endforeach
            </div>
            @if (filled($data['note'] ?? null))
                <p class="text-xs leading-[1.7] text-public-muted">{{ $data['note'] }}</p>
            @endif
        </section>
    @endif
@endif
