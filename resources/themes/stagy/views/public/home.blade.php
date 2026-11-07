{{-- The home page is the CMS page with the "home" slug in the current language. --}}
<x-public.layout :page="$page" :settings="$settings" :menu="$menu" :canonical="localized_route('home')">
    @if ($page !== null)
        @include('public.partials.blocks', ['blocks' => $page->blocks ?? []])
    @else
        <x-public.container as="section" class="flex flex-col gap-5 py-24">
            <x-public.heading :level="1" size="display" class="max-w-3xl">{{ $settings->get('general', [])['site_name'] ?? 'Stagy' }}</x-public.heading>
            <p class="max-w-xl text-public-muted">{{ $settings->get('general', [])['tagline'] ?? __('theme::site.footer_tagline') }}</p>
        </x-public.container>
    @endif
</x-public.layout>
