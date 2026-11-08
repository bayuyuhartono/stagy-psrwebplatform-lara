@inject('catalog', 'App\Features\Pricing\Services\PricingCatalog')
@inject('prices', 'App\Features\Pricing\Services\PriceFormatter')

@php($pricing = $catalog->for())
@php($plans = $pricing['plans'])
@php($locale = app()->getLocale())
@inject('themeSettings', 'App\Features\Themes\Services\ThemeSettings')
@php($demoUrl = localized_route('website.pages.show', $themeSettings->get('demo_page_slug', 'demo')))

@if ($plans->count() > 1 && (filled($pricing['groups']) || filled($pricing['included_in_all'])))
    <section class="bg-public-mist py-22">
        <x-public.container class="flex flex-col gap-10">
            <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" />

            @if (filled($pricing['groups']))
                <div class="flex flex-col gap-3">
                    <div class="relative overflow-x-auto rounded-[1.375rem] border border-public-line bg-public-paper" tabindex="0" role="region" aria-label="{{ $data['heading'] ?? __('theme::site.pricing.feature') }}">
                        <table class="w-full min-w-180 border-collapse text-left text-sm">
                            <thead>
                                <tr class="border-b border-public-line">
                                    <th scope="col" class="w-[34%] px-5 py-5 align-bottom text-xs font-normal tracking-[0.08em] text-public-muted">{{ __('theme::site.pricing.feature') }}</th>
                                    @foreach ($plans as $plan)
                                        <th scope="col" @class(['px-5 py-5 align-bottom font-normal', 'bg-public-mist' => $plan->is_highlighted])>
                                            <span class="block text-base">{{ $plan->name }}</span>
                                            <span class="mt-1 block text-xs text-public-muted">{{ __('theme::site.pricing.from') }} {{ $prices->format($plan->price, $locale) }}</span>
                                            @if (filled($plan->cta_label))
                                                <x-public.button :href="$plan->cta_url ?: $demoUrl" size="sm" :variant="$plan->is_highlighted ? 'accent' : 'outline'" class="mt-3">{{ __('theme::site.pricing.choose') }}<span class="sr-only"> {{ $plan->name }}</span></x-public.button>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            @foreach ($pricing['groups'] as $group)
                                <tbody>
                                    <tr class="bg-public-mist">
                                        <th scope="colgroup" colspan="{{ $plans->count() + 1 }}" class="px-5 py-3 text-xs font-normal tracking-[0.08em] text-public-accent">{{ $group['title'] }}</th>
                                    </tr>
                                    @foreach ($group['rows'] as $row)
                                        <tr class="border-t border-public-mint">
                                            <th scope="row" class="px-5 py-3.5 font-normal leading-[1.5]">{{ $row['label'] }}</th>
                                            @foreach ($row['cells'] as $index => $cell)
                                                <td @class(['px-5 py-3.5 leading-[1.5]', 'bg-public-mist/60' => $plans[$index]->is_highlighted])>
                                                    @if ($cell === true)
                                                        <x-public.icon name="check" class="size-4.5 text-public-accent" stroke-width="2.6" /><span class="sr-only">{{ __('theme::site.pricing.included') }}</span>
                                                    @elseif ($cell === false)
                                                        <span aria-hidden="true" class="text-public-line">–</span><span class="sr-only">{{ __('theme::site.pricing.not_included') }}</span>
                                                    @else
                                                        <span class="text-[0.8125rem]">{{ $cell }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                    <p class="text-xs text-public-muted md:hidden">{{ __('theme::site.pricing.scroll_hint') }}</p>
                    @if (filled($data['note'] ?? null))
                        <p class="text-xs leading-[1.7] text-public-muted">{{ $data['note'] }}</p>
                    @endif
                </div>
            @endif

            @if (($data['show_included_in_all'] ?? true) && filled($pricing['included_in_all']))
                @php($planNames = $plans->pluck('name')->join(', ', $locale === 'id' ? ', dan ' : ', and '))
                <div class="flex flex-wrap gap-x-14 gap-y-6 rounded-[1.375rem] bg-public-ink p-[clamp(1.5rem,4vw,2.5rem)] text-white">
                    <div class="flex min-w-0 flex-[1_1_16rem] flex-col gap-3">
                        <x-public.eyebrow tone="sage">{{ __('theme::site.pricing.included_in_all_eyebrow') }}</x-public.eyebrow>
                        <h3 class="text-xl leading-[1.35] font-normal">{{ __('theme::site.pricing.included_in_all_heading', ['count' => count($pricing['included_in_all']), 'plans' => $planNames]) }}</h3>
                    </div>
                    <ul class="grid min-w-0 flex-[2_1_28rem] grid-cols-[repeat(auto-fit,minmax(13rem,1fr))] gap-x-6 gap-y-3">
                        @foreach ($pricing['included_in_all'] as $feature)
                            <li class="flex items-start gap-3 text-[0.8125rem] leading-[1.6] text-public-on-ink">
                                <x-public.icon name="check" class="mt-0.5 size-4 shrink-0 text-public-sage" stroke-width="2.6" />
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-public.container>
    </section>
@endif
