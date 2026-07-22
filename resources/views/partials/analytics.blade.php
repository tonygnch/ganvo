{{--
    Google Analytics (GA4) — gtag.js. Renders ONLY in production with a
    configured measurement ID, so local/staging traffic never pollutes the
    property. Include once per page, inside <head>.
--}}
@if (app()->environment('production') && config('services.google_analytics.id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json(config('services.google_analytics.id')));
    </script>
@endif
