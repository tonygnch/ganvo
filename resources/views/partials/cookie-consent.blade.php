{{--
    Cookie-consent banner (Google Consent Mode v2). Self-contained — scoped
    styles + inline script — so it drops into any marketing page regardless
    of which stylesheet that page loads. Renders whenever a GA measurement
    ID is configured (in dev GA itself stays off, but the banner still works
    so the flow can be tested). The analytics partial defaults everything to
    denied; this banner flips analytics_storage on accept, and any
    [data-cookie-settings] link reopens it so a choice can be changed.

    Storage: localStorage "ganvo-consent" = {"v":1,"value":"granted|denied",
    "at":<ms>}. A choice older than 12 months expires and the banner asks
    again (legacy plain-string values are still honoured until they rotate).
--}}
@if (config('services.google_analytics.id'))
    <style>
        .cc {
            position: fixed; z-index: 70; left: 1.25rem; right: 1.25rem; bottom: 1.25rem;
            max-width: 26rem; padding: 1.4rem 1.5rem 1.5rem;
            background: color-mix(in srgb, var(--panel, #0a1020) 92%, transparent);
            border: 1px solid var(--line-2, rgba(99, 141, 255, 0.30));
            border-radius: 0.9rem;
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 1.5rem 3rem rgba(0, 0, 0, 0.45);
            color: var(--ink, #eef2fb);
            opacity: 0; transform: translateY(0.8rem);
            transition: opacity 0.35s ease, transform 0.35s ease;
            outline: none;
        }
        .cc.is-open { opacity: 1; transform: none; }
        .cc[hidden] { display: none; }
        .cc__label {
            margin: 0 0 0.7rem; font-family: var(--font-mono, 'JetBrains Mono', monospace);
            font-size: 0.66rem; letter-spacing: 0.16em; text-transform: uppercase;
            color: var(--accent, #4d8dff);
        }
        .cc__text { margin: 0 0 1.1rem; font-size: 0.85rem; line-height: 1.55; color: var(--ink-dim, #9aa7c4); }
        .cc__actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
        .cc__btn {
            appearance: none; cursor: pointer; border-radius: 999px;
            padding: 0.6rem 1.3rem; font: inherit; font-size: 0.82rem; font-weight: 600;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .cc__btn--accept { border: 1px solid transparent; background: var(--accent-strong, #2563eb); color: #fff; }
        .cc__btn--accept:hover { background: var(--accent-hover, #1d4ed8); }
        .cc__btn--decline { border: 1px solid var(--line-2, rgba(99, 141, 255, 0.30)); background: transparent; color: var(--ink, #eef2fb); }
        .cc__btn--decline:hover { border-color: var(--accent, #4d8dff); color: var(--accent, #4d8dff); }
        .cc__btn:focus-visible { outline: 2px solid var(--accent, #4d8dff); outline-offset: 2px; }
        @media (max-width: 640px) {
            .cc { left: 0.75rem; right: 0.75rem; bottom: 0.75rem; max-width: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .cc { transition: none; transform: none; }
        }
    </style>

    <div class="cc" id="cookieConsent" role="region" aria-label="{{ __('site.common.cookies.label') }}" tabindex="-1" hidden>
        <p class="cc__label">{{ __('site.common.cookies.label') }}</p>
        <p class="cc__text">{{ __('site.common.cookies.text') }}</p>
        <div class="cc__actions">
            <button type="button" class="cc__btn cc__btn--accept" data-cc-accept>{{ __('site.common.cookies.accept') }}</button>
            <button type="button" class="cc__btn cc__btn--decline" data-cc-decline>{{ __('site.common.cookies.decline') }}</button>
        </div>
    </div>

    <script>
        (function () {
            var KEY = 'ganvo-consent';
            var MAX_AGE = 365 * 24 * 60 * 60 * 1000; // re-ask after 12 months
            var root = document.getElementById('cookieConsent');
            if (!root) return;

            var hideTimer = null;
            var autoTimer = null;

            // Same shape as gtag() — if gtag.js is live it processes the push
            // immediately; if not (dev, blocked), it queues harmlessly.
            function gt() { (window.dataLayer = window.dataLayer || []).push(arguments); }

            function stored() {
                try {
                    var raw = localStorage.getItem(KEY);
                    if (!raw) return null;
                    if (raw === 'granted' || raw === 'denied') return raw; // legacy format
                    var o = JSON.parse(raw);
                    if (o && (o.value === 'granted' || o.value === 'denied')
                        && (!o.at || Date.now() - o.at < MAX_AGE)) {
                        return o.value;
                    }
                } catch (e) {}
                return null;
            }

            function setOpen(open, focus) {
                clearTimeout(hideTimer);
                if (open) {
                    root.hidden = false;
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            root.classList.add('is-open');
                            if (focus) root.focus({ preventScroll: true });
                        });
                    });
                } else {
                    root.classList.remove('is-open');
                    hideTimer = setTimeout(function () { root.hidden = true; }, 400);
                }
            }

            function clearGaCookies() {
                document.cookie.split(';').forEach(function (c) {
                    var name = c.split('=')[0].trim();
                    if (name.indexOf('_ga') !== 0) return;
                    var kill = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                    document.cookie = kill;
                    document.cookie = kill + '; domain=.' + location.hostname.split('.').slice(-2).join('.');
                });
            }

            function choose(value) {
                clearTimeout(autoTimer);
                try {
                    localStorage.setItem(KEY, JSON.stringify({ v: 1, value: value, at: Date.now() }));
                } catch (e) {}
                gt('consent', 'update', { analytics_storage: value });
                if (value === 'granted') {
                    // If gtag.js was skipped because of an earlier refusal, load it now.
                    if (window.__gaLoad) window.__gaLoad();
                } else {
                    clearGaCookies();
                }
                setOpen(false);
            }

            root.querySelector('[data-cc-accept]').addEventListener('click', function () { choose('granted'); });
            root.querySelector('[data-cc-decline]').addEventListener('click', function () { choose('denied'); });

            // Footer "Cookies" links reopen the banner so a choice can be changed.
            document.querySelectorAll('[data-cookie-settings]').forEach(function (el) {
                el.addEventListener('click', function (e) { e.preventDefault(); setOpen(true, true); });
            });

            if (!stored()) {
                // Small delay so the banner never competes with the page entrance;
                // bail if a choice was made in the meantime (e.g. via a settings link).
                autoTimer = setTimeout(function () {
                    if (!stored()) setOpen(true);
                }, 1200);
            }
        })();
    </script>
@endif
