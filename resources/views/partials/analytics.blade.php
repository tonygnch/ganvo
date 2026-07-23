{{--
    Google Analytics (GA4) — gtag.js with Consent Mode v2. Renders ONLY in
    production with a configured measurement ID, so local/staging traffic
    never pollutes the property. Include once per page, inside <head>.

    Consent: everything starts denied. gtag.js itself is only injected when
    the visitor has NOT explicitly declined — after a refusal nothing is
    loaded from Google at all. The cookie-consent partial flips
    analytics_storage to granted on accept (calling window.__gaLoad() so the
    script appears immediately even after an earlier refusal), and stores the
    choice in localStorage under "ganvo-consent" as {"v":1,"value":...,"at":...}.
--}}
@if (app()->environment('production') && config('services.google_analytics.id'))
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied'
        });
        (function () {
            var choice = null;
            try {
                var raw = localStorage.getItem('ganvo-consent');
                if (raw === 'granted' || raw === 'denied') { choice = raw; }
                else if (raw) {
                    var o = JSON.parse(raw);
                    if (o && (o.value === 'granted' || o.value === 'denied')
                        && (!o.at || Date.now() - o.at < 365 * 24 * 60 * 60 * 1000)) {
                        choice = o.value;
                    }
                }
            } catch (e) {}
            if (choice === 'granted') gtag('consent', 'update', { analytics_storage: 'granted' });
            gtag('js', new Date());
            gtag('config', @json(config('services.google_analytics.id')));
            var loaded = false;
            window.__gaLoad = function () {
                if (loaded) return;
                loaded = true;
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}';
                document.head.appendChild(s);
            };
            // An explicit refusal means nothing loads from Google — not even
            // cookieless pings. Undecided visitors get consent-denied pings only.
            if (choice !== 'denied') window.__gaLoad();
        })();
    </script>
@endif
