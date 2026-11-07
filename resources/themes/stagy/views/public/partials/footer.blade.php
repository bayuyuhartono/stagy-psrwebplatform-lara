@inject('contentUrls', 'App\Features\Seo\Services\PublicUrlGenerator')

@php
    $contact = $settings->get('contact', []);
    $social = $settings->get('social', []);
    $footerColumns = collect([
        'footer-product' => __('theme::site.footer_product'),
        'footer-help' => __('theme::site.footer_help'),
    ])->map(fn (string $heading, string $location): array => ['heading' => $heading, 'menu' => $menus[$location] ?? null])
        ->filter(fn (array $column): bool => filled($column['menu']?->rootItems));
    $socialLinks = collect(['instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'])
        ->filter(fn (string $label, string $network): bool => filled($social["{$network}_url"] ?? null));
@endphp

<footer class="border-t border-public-line bg-public-mist pt-14">
    <x-public.container class="flex flex-col">
        <div class="flex flex-wrap gap-x-14 gap-y-10 pb-12">
            <div class="flex min-w-0 flex-[1.4_1_17.5rem] flex-col gap-4.5">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" @if (blank($brandLogoUrl ?? null)) width="900" height="392" @endif class="h-11 w-auto self-start">
                <p class="max-w-80 text-[0.8125rem] leading-[1.85] text-public-muted">{{ $settings->get('general', [])['tagline'] ?? __('theme::site.footer_tagline') }}</p>
                @if ($socialLinks->isNotEmpty())
                    <div class="flex gap-2.5">
                        @foreach ($socialLinks as $network => $label)
                            <a href="{{ $social["{$network}_url"] }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.social_link', ['network' => $label, 'name' => $siteName]) }}" class="grid size-11 place-items-center rounded-xl border border-public-line bg-public-paper text-public-ink transition hover:border-public-accent">
                                <x-public.icon :name="$network" class="size-4.5" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($footerColumns as $column)
                <nav aria-label="{{ $column['heading'] }}" class="flex min-w-0 flex-[1_1_9.5rem] flex-col gap-1">
                    <x-public.eyebrow class="mb-2">{{ $column['heading'] }}</x-public.eyebrow>
                    @foreach ($column['menu']->rootItems as $item)
                        <a href="{{ $contentUrls->forMenuItem($item) }}" target="{{ $item->target->value }}" class="py-2 text-[0.8125rem] text-public-ink hover:text-public-accent">{{ $item->label }}</a>
                    @endforeach
                </nav>
            @endforeach

            <div class="flex min-w-0 flex-[1.6_1_18.75rem] flex-col gap-3.5 text-[0.8125rem] leading-[1.7]">
                <x-public.eyebrow class="mb-0.5">{{ __('theme::site.footer_contact') }}</x-public.eyebrow>
                @if (filled($whatsappUrl ?? null))
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="flex items-start gap-3 hover:text-public-accent">
                        <x-public.icon name="whatsapp" class="mt-0.5 size-4 shrink-0 text-public-accent" />
                        <span>{{ $contact['whatsapp'] ?? $contact['phone'] }}</span>
                    </a>
                @endif
                @if (filled($contact['email'] ?? null))
                    <a href="mailto:{{ $contact['email'] }}" class="flex items-start gap-3 hover:text-public-accent">
                        <x-public.icon name="mail" class="mt-0.5 size-4 shrink-0 text-public-accent" />
                        <span>{{ $contact['email'] }}</span>
                    </a>
                @endif
                @if (filled($contact['address'] ?? null))
                    <div class="flex items-start gap-3">
                        <x-public.icon name="pin" class="mt-0.5 size-4 shrink-0 text-public-accent" />
                        <address class="whitespace-pre-line not-italic">{{ $contact['address'] }}</address>
                    </div>
                @endif
                @if (filled($contact['business_hours'] ?? null))
                    <div class="flex items-start gap-3 text-public-muted">
                        <x-public.icon name="clock" class="mt-0.5 size-4 shrink-0 text-public-accent" />
                        <span><span class="sr-only">{{ __('theme::site.business_hours') }}: </span>{{ $contact['business_hours'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right padding keeps the bottom bar clear of the floating WhatsApp button. --}}
        <div class="flex flex-wrap items-center justify-between gap-x-7 gap-y-2 border-t border-public-line pt-5 pr-20 pb-6 text-xs tracking-[0.03em] text-public-muted">
            <span>© {{ now()->year }} {{ $siteName }}</span>
            @if ($theme['show_powered_by'] ?? true)
                <span>{{ __('theme::site.powered_by') }} <a href="{{ $theme['powered_by_url'] ?? 'https://pesoros.com' }}" target="_blank" rel="noopener" class="py-2.5 text-public-ink underline underline-offset-3">{{ $theme['powered_by_label'] ?? 'Pesoros' }}</a></span>
            @endif
        </div>
    </x-public.container>
</footer>
