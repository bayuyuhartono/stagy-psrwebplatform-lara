@inject('catalog', 'App\Features\Pricing\Services\PricingCatalog')
@inject('prices', 'App\Features\Pricing\Services\PriceFormatter')

@php($addons = $catalog->for()['addons'])
@php($locale = app()->getLocale())

@if ($addons->isNotEmpty())
    <section class="mx-auto flex max-w-290 flex-wrap items-start gap-14 px-4 py-22 sm:px-6">
        <div class="flex min-w-0 flex-[1_1_18.75rem] flex-col gap-4">
            @if (filled($data['eyebrow'] ?? null))
                <span class="text-xs tracking-[0.08em] text-public-accent">{{ $data['eyebrow'] }}</span>
            @endif
            @if (filled($data['heading'] ?? null))
                <h2 class="text-[clamp(1.5rem,2.8vw,2.125rem)] leading-[1.25] font-normal"><x-public.highlight :text="$data['heading']" /></h2>
            @endif
            @if (filled($data['intro'] ?? null))
                <p class="text-sm leading-[1.8] text-public-muted">{{ $data['intro'] }}</p>
            @endif
        </div>
        <div class="min-w-0 flex-[1.6_1_27.5rem] overflow-hidden rounded-[1.375rem] border border-public-line">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-public-mint text-xs tracking-[0.08em] text-public-deep">
                        <th scope="col" class="px-5 py-3.5 font-normal">{{ __('theme::site.pricing.item') }}</th>
                        <th scope="col" class="px-5 py-3.5 text-right font-normal">{{ __('theme::site.pricing.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($addons as $addon)
                        <tr @class(['border-t border-public-mint', 'bg-public-mist' => $loop->even])>
                            <th scope="row" class="px-5 py-3.5 leading-[1.5] font-normal">{{ $addon->name }}</th>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">{{ $prices->format($addon->price, $locale) }} @if (filled($addon->unit))<span class="text-xs text-public-muted">{{ $addon->unit }}</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
