@props(['page' => null, 'settings' => collect(), 'menu' => null, 'metaTitle' => null, 'metaDescription' => null, 'canonical' => null])

@php($general = $settings->get('general', []))
@php($seoDefaults = $settings->get('seo', []))
@php($experience = $settings->get('experience', []))
@php($contact = $settings->get('contact', []))
@php($social = $settings->get('social', []))
@php($siteName = $general['site_name'] ?? 'Pesoros')
@php($title = $page?->seo?->meta_title ?? $metaTitle ?? $seoDefaults['default_title'] ?? $siteName)
@php($description = $page?->seo?->meta_description ?? $metaDescription ?? $seoDefaults['default_description'] ?? ($general['tagline'] ?? 'Digital experiences built with purpose.'))
@php($canonicalUrl = $page?->seo?->canonical_url ?? $canonical ?? localized_route('home'))
@php($ogImageUrl = $page?->seo?->ogImage?->url ?? ($defaultSocialImageUrl ?? null))
@php($structuredData = $page?->seo?->structured_data)
@php($integrations = $integrations ?? [])
@php($theme = $theme ?? [])
@php($headerIsSolid = ($theme['header_style'] ?? 'overlay') === 'solid')

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="robots" content="{{ ($page?->seo?->robots_index ?? true) ? 'index' : 'noindex' }},{{ ($page?->seo?->robots_follow ?? true) ? 'follow' : 'nofollow' }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @if (count($alternateUrls ?? []) > 1)
            @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
                <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls[config('localization.default')] ?? reset($alternateUrls) }}">
        @endif
        <meta property="og:title" content="{{ $page?->seo?->og_title ?? $title }}">
        <meta property="og:description" content="{{ $page?->seo?->og_description ?? $description }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta name="twitter:card" content="{{ $page?->seo?->twitter_card ?? 'summary_large_image' }}">
        @if (filled($ogImageUrl))
            <meta property="og:image" content="{{ $ogImageUrl }}">
            <meta name="twitter:image" content="{{ $ogImageUrl }}">
            @if (filled($page?->seo?->ogImage?->alt_text))
                <meta property="og:image:alt" content="{{ $page->seo->ogImage->alt_text }}">
            @endif
        @endif
        @if (filled($structuredData))
            {{-- JSON_HEX_TAG keeps a stray </script> in the payload from breaking out of the block. --}}
            <script type="application/ld+json">{!! json_encode($structuredData, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif
        @include('public.partials.integrations-head', ['integrations' => $integrations])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-public-paper font-sans text-public-ink antialiased selection:bg-public-accent selection:text-public-ink">
        @include('public.partials.integrations-body', ['integrations' => $integrations])
        @if (($theme['show_announcement_bar'] ?? true) && ($experience['announcement_enabled'] ?? false) && filled($experience['announcement'] ?? null))
            <div class="bg-public-accent px-5 py-2.5 text-center text-xs font-bold text-public-ink">{{ $experience['announcement'] }}</div>
        @endif

        <header @class([
            'inset-x-0 top-0 z-40 border-b border-white/10 text-white',
            'absolute' => ! $headerIsSolid,
            'sticky bg-public-ink' => $headerIsSolid,
        ])>
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-5 sm:px-8 lg:px-10">
                <a href="{{ localized_route('home') }}" class="flex items-center gap-3 font-bold tracking-tight"><span class="grid size-10 place-items-center rounded-full bg-public-accent text-sm text-public-ink">P</span><span>{{ $siteName }}</span></a>
                <nav class="hidden items-center gap-7 text-sm font-semibold text-white/70 lg:flex" aria-label="Main navigation">
                    @forelse ($menu?->rootItems ?? [] as $item)
                        <a href="{{ $item->type->value === 'page' ? '/'.ltrim((string) $item->page?->slug, '/') : $item->url }}" target="{{ $item->target->value }}" class="transition hover:text-public-accent">{{ $item->label }}</a>
                    @empty
                        <a href="#services" class="transition hover:text-public-accent">Services</a><a href="#work" class="transition hover:text-public-accent">Work</a><a href="#about" class="transition hover:text-public-accent">About</a>
                    @endforelse
                </nav>
                <div class="flex items-center gap-3"><a href="#contact" class="hidden rounded-full border border-white/25 px-5 py-2.5 text-sm font-bold transition hover:border-public-accent hover:bg-public-accent hover:text-public-ink sm:inline-flex">Start a project</a><button data-public-menu-toggle type="button" class="grid size-11 place-items-center rounded-full border border-white/20 lg:hidden" aria-label="Open navigation" aria-expanded="false"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg></button></div>
            </div>
            <nav data-public-mobile-menu class="hidden border-t border-white/10 bg-public-ink px-5 py-5 lg:hidden" aria-label="Mobile navigation"><div class="grid gap-1">@forelse ($menu?->rootItems ?? [] as $item)<a href="{{ $item->type->value === 'page' ? '/'.ltrim((string) $item->page?->slug, '/') : $item->url }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-white/75 hover:bg-white/5 hover:text-public-accent">{{ $item->label }}</a>@empty<a href="#services" class="rounded-xl px-4 py-3">Services</a><a href="#work" class="rounded-xl px-4 py-3">Work</a><a href="#about" class="rounded-xl px-4 py-3">About</a>@endforelse</div></nav>
        </header>

        <main>{{ $slot }}</main>

        <footer id="contact" class="bg-public-ink text-white">
            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28">
                <div class="grid gap-12 border-b border-white/10 pb-16 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div><p class="text-xs font-bold uppercase tracking-[0.22em] text-public-accent">Have a project in mind?</p><h2 class="mt-5 max-w-4xl text-5xl font-semibold leading-[0.95] tracking-[-0.05em] sm:text-6xl lg:text-8xl">Let’s make something <span class="text-white/35">remarkable.</span></h2></div>
                    <a href="mailto:{{ $contact['email'] ?? 'hello@example.com' }}" class="grid size-28 place-items-center rounded-full bg-public-accent text-center text-sm font-bold text-public-ink transition hover:scale-105 sm:size-36">Get in<br>touch ↗</a>
                </div>
                <div class="grid gap-8 pt-10 text-sm text-white/55 md:grid-cols-3"><div><p class="font-bold text-white">{{ $siteName }}</p><p class="mt-2 max-w-xs">{{ $general['tagline'] ?? $theme['footer_tagline'] ?? 'Strategy, design, and technology for ambitious organisations.' }}</p></div><div><p>{{ $contact['email'] ?? 'hello@example.com' }}</p><p class="mt-1">{{ $contact['phone'] ?? '' }}</p></div>@if ($theme['show_footer_social'] ?? true)<div class="flex gap-5 md:justify-end">@foreach (['instagram_url' => 'Instagram', 'linkedin_url' => 'LinkedIn', 'x_url' => 'X'] as $key => $label)@if (filled($social[$key] ?? null))<a href="{{ $social[$key] }}" class="hover:text-public-accent">{{ $label }}</a>@endif @endforeach</div>@endif</div>
            </div>
        </footer>
    </body>
</html>
