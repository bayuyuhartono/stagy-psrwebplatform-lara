@inject('publicSettings', 'App\Features\WebsiteSettings\Services\PublicWebsiteSettings')
@inject('menuRepository', 'App\Features\Menus\Repositories\MenuRepository')
@inject('contentUrls', 'App\Features\Seo\Services\PublicUrlGenerator')
@inject('whatsAppLink', 'App\Features\WebsiteSettings\Services\WhatsAppLink')

@php
    $settings = $publicSettings->forCurrentLocale();
    $menu = $menuRepository->findActiveByLocationOrNull('header');
    $contactWhatsappUrl = $whatsAppLink->url($settings->get('contact', [])['whatsapp'] ?? $settings->get('contact', [])['phone'] ?? null);
    $highlight = '<span class="text-public-accent">'.e(__('theme::site.not_found.highlight')).'</span>';
@endphp

<x-public.layout :settings="$settings" :menu="$menu" :meta-title="__('theme::site.not_found.meta_title')" :meta-description="__('theme::site.not_found.meta_description')">
    <section class="mx-auto flex max-w-290 flex-wrap items-center gap-14 px-4 pt-20 pb-24 sm:px-6">
        <div class="flex min-w-0 flex-[1_1_27.5rem] flex-col gap-6">
            <span class="self-start rounded-full bg-public-mint px-3 py-2 text-xs tracking-[0.06em] text-public-deep">{{ __('theme::site.not_found.eyebrow') }}</span>
            {{-- The highlight is escaped above; only its wrapping span is markup. --}}
            <h1 class="text-[clamp(1.875rem,4vw,3rem)] leading-[1.15] font-normal">{!! __('theme::site.not_found.heading', ['highlight' => $highlight]) !!}</h1>
            <p class="max-w-xl text-public-muted">{{ __('theme::site.not_found.body') }}</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ localized_route('home') }}" class="inline-flex min-h-12 items-center rounded-xl bg-public-ink px-5.5 text-sm tracking-[0.04em] text-white transition hover:bg-public-deep">{{ __('theme::site.not_found.go_home') }}</a>
                @if (filled($contactWhatsappUrl))
                    <a href="{{ $contactWhatsappUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center rounded-xl border-[1.5px] border-public-line px-5.5 text-sm tracking-[0.04em] text-public-ink transition hover:border-public-accent">{{ __('theme::site.chat_whatsapp') }}</a>
                @endif
            </div>
            @if (filled($menu?->rootItems))
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-[0.8125rem] text-public-muted">
                    <span>{{ __('theme::site.not_found.or_go_to') }}</span>
                    @foreach ($menu->rootItems as $item)
                        <a href="{{ $contentUrls->forMenuItem($item) }}" class="py-2.5 text-public-ink underline underline-offset-4 hover:text-public-accent">{{ $item->label }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- A scanner result that could not find the "ticket" for this URL. --}}
        <div class="flex min-w-0 flex-[0_1_25rem] justify-center" aria-hidden="true">
            <div class="flex w-full justify-center rounded-[1.75rem] bg-public-mint px-12 py-10">
                <div class="w-64 rounded-[2.25rem] bg-public-ink p-2.5 shadow-[0_24px_48px_rgba(30,41,86,0.2)]">
                    <div class="overflow-hidden rounded-[1.75rem] bg-public-paper">
                        <div class="flex justify-between bg-public-ink/90 px-5 pt-5 pb-4 text-[0.6875rem] tracking-[0.08em] text-white">
                            <span>{{ __('theme::site.not_found.scan_result') }}</span><span>{{ __('theme::site.not_found.gate') }}</span>
                        </div>
                        <div class="flex flex-col items-center gap-3 px-5 pt-7 pb-6 text-center">
                            <span class="text-5xl leading-none text-public-accent">404</span>
                            <span class="text-[0.8125rem]">{{ __('theme::site.not_found.ticket_not_found') }}</span>
                            <div class="mt-2 flex w-full justify-between border-t border-dashed border-public-line pt-3 text-[0.6875rem] text-public-muted">
                                <span>{{ __('theme::site.not_found.status') }}</span><span>{{ __('theme::site.not_found.not_registered') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public.layout>
