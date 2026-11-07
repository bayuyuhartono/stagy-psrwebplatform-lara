{{--
    Body-placed integration tags — the no-JS fallbacks that must sit immediately
    after the opening <body>, plus user-authored body scripts.
--}}
@props(['integrations' => []])

@if (filled($integrations['google-tag-manager']['container_id'] ?? null))
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($integrations['google-tag-manager']['container_id']) }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif

@if (filled($integrations['meta-pixel']['pixel_id'] ?? null))
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
             src="https://www.facebook.com/tr?id={{ urlencode($integrations['meta-pixel']['pixel_id']) }}&ev=PageView&noscript=1">
    </noscript>
@endif

{{-- Raw by design: authored in the CMS by users holding integrations.update. --}}
@if (filled($integrations['custom-scripts']['body_html'] ?? null))
    {!! $integrations['custom-scripts']['body_html'] !!}
@endif
