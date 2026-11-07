{{--
    Head-placed integration tags. Receives $integrations: enabled providers keyed by
    provider value, carrying public config only (see PublicIntegrationResolver).

    Google Maps and reCAPTCHA are deliberately absent — both are consumed by the
    specific components that need them rather than loaded on every page.
--}}
@props(['integrations' => []])

@if (filled($integrations['google-search-console']['verification_code'] ?? null))
    <meta name="google-site-verification" content="{{ $integrations['google-search-console']['verification_code'] }}">
@endif

@if (filled($integrations['google-tag-manager']['container_id'] ?? null))
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', @js($integrations['google-tag-manager']['container_id']));
    </script>
@endif

@if (filled($integrations['google-analytics']['measurement_id'] ?? null))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($integrations['google-analytics']['measurement_id']) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @js($integrations['google-analytics']['measurement_id']));
    </script>
@endif

@if (filled($integrations['meta-pixel']['pixel_id'] ?? null))
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
            t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @js($integrations['meta-pixel']['pixel_id']));
        fbq('track', 'PageView');
    </script>
@endif

@if (filled($integrations['microsoft-clarity']['project_id'] ?? null))
    <script>
        (function (c, l, a, r, i, t, y) {
            c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments) };
            t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
            y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
        })(window, document, 'clarity', 'script', @js($integrations['microsoft-clarity']['project_id']));
    </script>
@endif

{{-- Raw by design: authored in the CMS by users holding integrations.update. --}}
@if (filled($integrations['custom-scripts']['head_html'] ?? null))
    {!! $integrations['custom-scripts']['head_html'] !!}
@endif
