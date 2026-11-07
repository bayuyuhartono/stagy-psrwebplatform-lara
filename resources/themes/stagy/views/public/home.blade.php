{{-- The home page is the CMS page with the "home" slug in the current language. --}}
<x-public.layout :page="$page" :settings="$settings" :menu="$menu" :canonical="localized_route('home')">
    @if ($page !== null)
        @include('public.partials.blocks', ['blocks' => $page->blocks ?? []])
    @else
        <section class="mx-auto flex max-w-290 flex-col gap-5 px-4 py-24 sm:px-6">
            <h1 class="max-w-3xl text-[clamp(1.875rem,4vw,3rem)] leading-[1.15] font-normal">{{ $settings->get('general', [])['site_name'] ?? 'Stagy' }}</h1>
            <p class="max-w-xl text-public-muted">{{ $settings->get('general', [])['tagline'] ?? __('theme::site.footer_tagline') }}</p>
        </section>
    @endif
</x-public.layout>
