@props(['page' => null, 'settings' => collect(), 'menu' => null, 'metaTitle' => null, 'metaDescription' => null, 'canonical' => null])

@php($general = $settings->get('general', []))
@php($seoDefaults = $settings->get('seo', []))
@php($experience = $settings->get('experience', []))
@php($siteName = $general['site_name'] ?? 'Stagy')
@php($title = $page?->seo?->meta_title ?? $metaTitle ?? $seoDefaults['default_title'] ?? $siteName)
@php($description = $page?->seo?->meta_description ?? $metaDescription ?? $seoDefaults['default_description'] ?? ($general['tagline'] ?? __('theme::site.footer_tagline')))
@php($canonicalUrl = $page?->seo?->canonical_url ?? $canonical ?? localized_route('home'))
@php($ogImageUrl = $page?->seo?->ogImage?->url ?? ($defaultSocialImageUrl ?? null))
@php($structuredData = $page?->seo?->structured_data)
@php($integrations = $integrations ?? [])
@php($theme = $theme ?? [])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        @if (filled($brandFaviconUrl ?? null))
            <link rel="icon" href="{{ $brandFaviconUrl }}">
            <link rel="apple-touch-icon" href="{{ $brandFaviconUrl }}">
        @else
            <link rel="icon" type="image/png" sizes="32x32" href="{{ Vite::asset('resources/themes/stagy/assets/images/favicon-32.png') }}">
            <link rel="icon" type="image/png" sizes="16x16" href="{{ Vite::asset('resources/themes/stagy/assets/images/favicon-16.png') }}">
            <link rel="icon" type="image/png" sizes="512x512" href="{{ Vite::asset('resources/themes/stagy/assets/images/favicon-512.png') }}">
            <link rel="apple-touch-icon" sizes="180x180" href="{{ Vite::asset('resources/themes/stagy/assets/images/apple-touch-icon.png') }}">
        @endif
        <meta name="theme-color" content="#1E2956">
        @include('public.partials.seo-head')
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/themes/stagy/stagy.css', 'resources/themes/stagy/stagy.js'])
    </head>
    <body class="min-h-screen bg-public-paper font-public text-[0.9375rem] leading-[1.75] tracking-[0.01em] text-public-ink antialiased selection:bg-public-accent selection:text-white">
        <a href="#content" class="sr-only z-50 rounded-lg bg-public-ink px-4 py-3 text-sm text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">{{ __('theme::site.skip_to_content') }}</a>
        @include('public.partials.integrations-body', ['integrations' => $integrations])

        @if (($experience['announcement_enabled'] ?? false) && filled($experience['announcement'] ?? null))
            <div class="bg-public-accent px-4 py-2.5 text-center text-xs tracking-[0.04em] text-white">{{ $experience['announcement'] }}</div>
        @endif

        @php($logoUrl = $brandLogoUrl ?? Vite::asset('resources/themes/stagy/assets/images/logo.png'))
        @include('public.partials.header', ['siteName' => $siteName, 'logoUrl' => $logoUrl])

        <main id="content">{{ $slot }}</main>

        @include('public.partials.footer', ['siteName' => $siteName, 'logoUrl' => $logoUrl])

        @if (($theme['show_whatsapp_button'] ?? true) && filled($whatsappUrl ?? null))
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="{{ __('theme::site.chat_whatsapp') }}" class="fixed right-5 bottom-5 z-30 grid size-14 place-items-center rounded-full bg-public-accent text-white shadow-[0_10px_24px_rgba(30,41,86,0.28)] transition hover:bg-public-deep focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-public-ink">
                <x-public.icon name="whatsapp" class="size-6.5" />
            </a>
        @endif
    </body>
</html>
