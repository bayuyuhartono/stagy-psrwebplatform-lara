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

<x-public.layout :settings="$settings" :menu="$menu" :meta-title="__('theme::site.not_found.meta_title')" :meta-description="__('theme::site.not_found.meta_description')" :noindex="true">
    <x-public.container as="section" class="flex flex-wrap items-center gap-14 pt-20 pb-24">
        <div class="flex min-w-0 flex-[1_1_27.5rem] flex-col gap-6">
            <x-public.badge tone="mint" class="self-start">{{ __('theme::site.not_found.eyebrow') }}</x-public.badge>
            {{-- The highlight is escaped above; only its wrapping span is markup. --}}
            <x-public.heading :level="1" size="display">{!! __('theme::site.not_found.heading', ['highlight' => $highlight]) !!}</x-public.heading>
            <p class="max-w-xl text-public-muted">{{ __('theme::site.not_found.body') }}</p>
            <div class="flex flex-wrap gap-3">
                <x-public.button :href="localized_route('home')">{{ __('theme::site.not_found.go_home') }}</x-public.button>
                @if (filled($contactWhatsappUrl))
                    <x-public.button :href="$contactWhatsappUrl" variant="outline">{{ __('theme::site.chat_whatsapp') }}</x-public.button>
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
        <x-public.phone-mockup>
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
        </x-public.phone-mockup>
    </x-public.container>
</x-public.layout>
