<!doctype html>
<html lang="{{ app()->getLocale() }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    @include('partials.analytics')
    <title>{{ $cs['page_title'] ?? __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">
    @include('partials.social-meta', [
        'title'       => ($cs['page_title'] ?? __('site.marketing.coming_soon.title')) . ' — Ganvo',
        'description' => $cs['meta_description'] ?? __('site.marketing.coming_soon.meta_description'),
    ])

    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: #05070e;
            color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }

        /* -------- Full-bleed video background — same clip as the main site's hero -------- */
        .cs-bg { position: fixed; inset: 0; z-index: -2; overflow: hidden; }
        .cs-bg video { width: 100%; height: 100%; object-fit: cover; opacity: 0.85; }
        .cs-veil {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(ellipse 90% 70% at 50% 40%, rgba(5,7,14,0.35), rgba(5,7,14,0.75) 65%, rgba(5,7,14,0.92));
        }

        /* -------- Centered logo + single headline -------- */
        .cs-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: clamp(1.5rem, 4vh, 2.5rem);
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .cs-headline {
            margin: 0;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.15;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
        }
        .cs-headline .accent { color: #3b82f6; }

        /* Logo — scales down on narrow phones instead of staying pinned at
           its desktop size; `!important` is needed because the lockup
           component sets height inline. Unchanged above ~600px. */
        .cs-logo img { height: clamp(40px, 16vw, 96px) !important; width: auto !important; }

        /* -------- Entrance: logo settles in first, headline follows -------- */
        .cs-logo, .cs-headline {
            opacity: 0;
            animation: csRise 0.9s cubic-bezier(.16, .84, .44, 1) both;
        }
        .cs-logo     { animation-delay: .1s; }
        .cs-headline { animation-delay: .35s; }
        @keyframes csRise {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* -------- Footer: copyright + language switch only -------- */
        footer.cs-foot {
            flex-shrink: 0;
            padding: 1.25rem 1.5rem;
            text-align: center;
            color: rgba(245, 245, 247, 0.55);
            font-size: 0.75rem;
            letter-spacing: 0.04em;
        }
        footer.cs-foot a { color: rgba(245, 245, 247, 0.7); text-decoration: none; }
        footer.cs-foot a:hover { color: #fff; }
        footer.cs-foot a.active { color: #fff; font-weight: 600; }
        footer.cs-foot .sep { color: rgba(245, 245, 247, 0.3); margin: 0 .5rem; }

        /* No-JS-independent reduced-motion fallback: hide the video, show its
           poster frame as a static background instead. */
        @media (prefers-reduced-motion: reduce) {
            .cs-bg video { display: none; }
            .cs-bg { background: url('{{ asset('images/marketing/hero.png') }}') center / cover no-repeat; }
            .cs-logo, .cs-headline { animation: none; opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
    @endphp

    <div class="cs-bg" aria-hidden="true">
        <video autoplay muted loop playsinline preload="auto" poster="{{ asset('images/marketing/hero.png') }}" id="csBgVideo">
            <source src="{{ asset('images/marketing/hero.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="cs-veil" aria-hidden="true"></div>

    <script>
        // The `autoplay` attribute alone isn't always honored — nudge it
        // explicitly, same fix the main marketing hero video uses.
        (function () {
            var v = document.getElementById('csBgVideo');
            var kick = function () { v.play().catch(function () {}); };
            kick();
            v.addEventListener('canplay', kick, { once: true });
        })();
    </script>

    <main class="cs-main">
        <div class="cs-logo">
            <x-brand-lockup size="xl" />
        </div>
        <h1 class="cs-headline">
            {{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}
            <span class="accent">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
        </h1>
    </main>

    <footer class="cs-foot">
        © {{ date('Y') }} Ganvo
        <span class="sep">·</span>
        <a href="#" data-cookie-settings>{{ __('site.common.cookies.settings') }}</a>
        <span class="sep">·</span>
        @foreach ($languages as $code => $name)
            <a href="/lang/{{ $code }}"
               class="@if($currentLocale === $code) active @endif"
               aria-label="{{ $name }}">{{ strtoupper($code) }}</a>@if(! $loop->last) <span style="color: rgba(245,245,247,0.3)">/</span>@endif
        @endforeach
    </footer>
@include('partials.cookie-consent')
</body>
</html>
