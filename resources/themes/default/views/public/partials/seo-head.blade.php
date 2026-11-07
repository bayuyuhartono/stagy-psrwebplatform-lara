{{-- SEO, social, alternate-language and integration tags shared by every theme's public layout. Expects the layout's computed variables. --}}
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
