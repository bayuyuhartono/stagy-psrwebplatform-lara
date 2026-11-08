{{-- SEO, social, alternate-language and integration tags shared by every theme's public layout. Expects the layout's computed variables. --}}
@inject('structuredDataBuilder', 'App\Features\Seo\Services\StructuredDataBuilder')

@php
    // $noindex marks pages that must never be indexed (error pages, empty fallbacks); they also get no canonical or hreflang.
    $forceNoindex = (bool) ($noindex ?? false);
    $isIndexable = ! $forceNoindex && ($page?->seo?->robots_index ?? true);
    $currentLocale = app()->getLocale();
    $ogLocale = fn (string $locale): string => config("localization.supported.{$locale}.og_locale", $locale);
    $article = $page instanceof \App\Models\Post ? $page : null;
    $schemas = $forceNoindex ? [] : $structuredDataBuilder->build($page, [
        'site_name' => $siteName,
        'home_url' => localized_route('home'),
        'canonical_url' => $canonicalUrl,
        'title' => $page?->seo?->og_title ?? $page?->title ?? $title,
        'description' => $description,
        'image_url' => $ogImageUrl,
        'logo_url' => $organizationLogoUrl ?? null,
        'locale' => $currentLocale,
        'is_home' => request()->routeIs('home', '*.home'),
        'settings' => $settings,
    ]);
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $isIndexable ? 'index' : 'noindex' }},{{ ($page?->seo?->robots_follow ?? true) ? 'follow' : 'nofollow' }}">
@unless ($forceNoindex)
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endunless
@if ($isIndexable && count($alternateUrls ?? []) > 1)
    @foreach ($alternateUrls as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls[config('localization.default')] ?? reset($alternateUrls) }}">
@endif
<meta property="og:title" content="{{ $page?->seo?->og_title ?? $title }}">
<meta property="og:description" content="{{ $page?->seo?->og_description ?? $description }}">
<meta property="og:type" content="{{ $article ? 'article' : 'website' }}">
@unless ($forceNoindex)
    <meta property="og:url" content="{{ $canonicalUrl }}">
@endunless
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $ogLocale($currentLocale) }}">
@if ($isIndexable)
    @foreach (array_keys($alternateUrls ?? []) as $alternateLocale)
        @if ($alternateLocale !== $currentLocale)
            <meta property="og:locale:alternate" content="{{ $ogLocale($alternateLocale) }}">
        @endif
    @endforeach
@endif
@if ($article)
    @if ($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toAtomString() }}">
    @endif
    @if ($article->updated_at)
        <meta property="article:modified_time" content="{{ $article->updated_at->toAtomString() }}">
    @endif
    @if ($article->relationLoaded('categories') && $article->categories->isNotEmpty())
        <meta property="article:section" content="{{ $article->categories->first()->name }}">
    @endif
    @if ($article->relationLoaded('tags'))
        @foreach ($article->tags as $articleTag)
            <meta property="article:tag" content="{{ $articleTag->name }}">
        @endforeach
    @endif
@endif
{{-- A large-image card without an image renders as an empty box, so fall back to the compact card. --}}
<meta name="twitter:card" content="{{ filled($ogImageUrl) ? ($page?->seo?->twitter_card ?? 'summary_large_image') : 'summary' }}">
@if (filled($ogImageUrl))
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @if (filled($ogImageAlt ?? null))
        <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    @endif
@endif
@foreach ($schemas as $schema)
    {{-- JSON_HEX_TAG keeps a stray </script> in the payload from breaking out of the block. --}}
    <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
@include('public.partials.integrations-head', ['integrations' => $integrations])
