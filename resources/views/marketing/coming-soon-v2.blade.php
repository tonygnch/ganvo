<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <title>{{ $cs['page_title'] ?? __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">

    <style>
        :root {
            --bg:        #050817;
            --bg-deep:   #020310;
            --grid:      rgba(120, 180, 255, .04);
            --hair:      rgba(140, 180, 255, .08);
            --hair-soft: rgba(140, 180, 255, .04);
            --text:      #e8edff;
            --text-dim:  #92a0c8;
            --text-faint:#5b6789;
            --cyan:      #00f0ff;
            --magenta:   #ff2dd0;
            --violet:    #7c3aed;
            --primary-gradient: linear-gradient(135deg, var(--cyan), var(--violet) 50%, var(--magenta));
        }

        * { box-sizing: border-box; }
        /* Locked viewport, no scroll — splash should fit any reasonable screen
           in one paint, same approach as v1. The escape hatch at the bottom
           handles freakishly-short landscape phones. */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; height: 100dvh;
            overflow: hidden;
            background: var(--bg-deep);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, 'Inter', sans-serif;
        }
        body { display: flex; flex-direction: column; position: relative; }
        a { color: var(--cyan); text-decoration: none; }

        /* -------- Background atmospherics (z-index: 0) -------- */
        .bg-mesh {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 600px 400px at 20% 30%, rgba(124, 58, 237, .35), transparent 60%),
                radial-gradient(ellipse 700px 500px at 80% 70%, rgba(255, 45, 208, .25), transparent 60%),
                radial-gradient(ellipse 500px 400px at 50% 100%, rgba(0, 240, 255, .25), transparent 60%);
            filter: blur(2px);
            animation: meshShift 24s ease-in-out infinite alternate;
        }
        @keyframes meshShift {
            0%   { transform: translate(0, 0) scale(1); }
            50%  { transform: translate(-30px, 20px) scale(1.05); }
            100% { transform: translate(30px, -20px) scale(.98); }
        }
        .bg-grid {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
        }
        .bg-scanlines {
            position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .3;
            background-image: repeating-linear-gradient(0deg,
                rgba(255, 255, 255, .015) 0px, rgba(255, 255, 255, .015) 1px,
                transparent 1px, transparent 3px);
        }
        .bg-spotlight {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 400px at var(--mx, 50%) var(--my, 50%), rgba(0, 240, 255, .08), transparent 70%);
        }

        /* -------- Abstract floating SVG storefront wireframes (z-index: 1) --------
           Three SVGs sit absolutely in the corners. Each is decorative — they
           draw themselves on a stroke-dasharray loop so they feel "alive" without
           depicting real content. Hidden on small screens. */
        .wireframe {
            position: absolute; z-index: 1; pointer-events: none;
            opacity: .25;
            animation: drift 18s ease-in-out infinite;
        }
        .wireframe svg { display: block; width: 100%; height: 100%; overflow: visible; }
        .wireframe svg * {
            stroke: var(--cyan);
            stroke-width: 1;
            fill: none;
            stroke-dasharray: 200;
            stroke-dashoffset: 200;
            animation: drawIn 4s ease-out forwards infinite;
        }
        @keyframes drawIn {
            0%   { stroke-dashoffset: 200; opacity: 0; }
            20%  { opacity: 1; }
            70%  { stroke-dashoffset: 0; opacity: 1; }
            100% { stroke-dashoffset: -200; opacity: 0; }
        }
        @keyframes drift {
            0%, 100% { transform: translate(0, 0); }
            50%      { transform: translate(0, -16px); }
        }
        .wf-1 { top: 6%; left: 4%; width: 220px; height: 160px; animation-delay: 0s; transform: rotate(-3deg); }
        .wf-1 svg * { animation-delay: 0s; }
        .wf-2 { top: 10%; right: 5%; width: 260px; height: 190px; animation-delay: -6s; transform: rotate(4deg); }
        .wf-2 svg * { animation-delay: -1.5s; stroke: var(--magenta); }
        .wf-3 { bottom: 12%; right: 7%; width: 200px; height: 150px; animation-delay: -12s; transform: rotate(-2deg); }
        .wf-3 svg * { animation-delay: -3s; stroke: var(--violet); }
        @media (max-width: 900px) { .wf-2 { display: none; } }
        @media (max-width: 600px) { .wireframe { display: none; } }

        /* -------- Theme palette swatches floating bottom-left -------- */
        .palettes {
            position: absolute; z-index: 1; pointer-events: none;
            bottom: 4rem; left: 2rem;
            display: flex; gap: .5rem;
            opacity: .8;
        }
        .palette {
            display: flex; align-items: center; gap: .5rem;
            padding: .375rem .625rem .375rem .375rem;
            background: rgba(15, 20, 45, .55);
            border: 1px solid var(--hair);
            border-radius: 999px;
            backdrop-filter: blur(8px);
            font: 600 0.625rem/1 ui-monospace, 'JetBrains Mono', SFMono-Regular, monospace;
            color: var(--text-faint);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            animation: paletteFloat 8s ease-in-out infinite;
        }
        .palette .swatch { width: 14px; height: 14px; border-radius: 50%; box-shadow: 0 0 8px currentColor; }
        @keyframes paletteFloat {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-4px); }
        }
        .palette:nth-child(1) { animation-delay: 0s; }
        .palette:nth-child(2) { animation-delay: -1.5s; }
        .palette:nth-child(3) { animation-delay: -3s; }
        .palette:nth-child(4) { animation-delay: -4.5s; }
        .palette:nth-child(5) { animation-delay: -6s; }
        @media (max-width: 900px) { .palettes { bottom: 3.5rem; left: 50%; transform: translateX(-50%); flex-wrap: wrap; justify-content: center; max-width: 90%; } }

        /* -------- Top status bar -------- */
        .statusbar {
            position: relative; z-index: 3; flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: .875rem 1.5rem;
            border-bottom: 1px solid var(--hair-soft);
            font: 500 0.6875rem/1 ui-monospace, 'JetBrains Mono', monospace;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-faint);
            backdrop-filter: blur(8px);
            background: rgba(5, 8, 23, .3);
        }
        .statusbar .left  { display: flex; gap: 1.25rem; align-items: center; }
        .statusbar .right { display: flex; gap: 1rem; align-items: center; }
        .statusbar .sep   { color: rgba(140, 180, 255, .15); }
        .statusbar .dot   { width: 6px; height: 6px; border-radius: 50%; background: var(--cyan); box-shadow: 0 0 8px var(--cyan); animation: livePulse 2s ease-in-out infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .statusbar a { color: var(--text-faint); transition: color .15s; }
        .statusbar a:hover { color: var(--cyan); }
        .statusbar a.active { color: var(--text); }

        /* -------- Hero (centered single column, fills remaining viewport) -------- */
        .hero {
            position: relative; z-index: 2;
            flex: 1; min-height: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1.5rem 1.5rem 6rem;
            text-align: center;
        }
        .lockup-wrap { margin: 0 0 1.75rem; }
        .lockup-wrap img { filter: drop-shadow(0 0 24px rgba(0, 240, 255, .25)); }

        .eyebrow {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .375rem .875rem;
            background: rgba(0, 240, 255, .08);
            border: 1px solid rgba(0, 240, 255, .25);
            border-radius: 999px;
            font: 600 0.6875rem/1 ui-monospace, monospace;
            color: var(--cyan);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: 0 0 1.25rem;
        }
        .eyebrow::before {
            content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--cyan);
            box-shadow: 0 0 8px var(--cyan); animation: livePulse 1.5s ease-in-out infinite;
        }

        h1 {
            font-size: clamp(2.25rem, 5.5vw, 4rem);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin: 0 0 1.25rem;
            max-width: 720px;
        }
        h1 .gradient {
            background: var(--primary-gradient);
            background-clip: text; -webkit-background-clip: text;
            color: transparent;
            background-size: 200% auto;
            animation: gradientSlide 6s linear infinite;
        }
        @keyframes gradientSlide {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        h1 .reveal { display: inline-block; opacity: 0; transform: translateY(8px); animation: revealUp .6s ease forwards; }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }

        .lead {
            color: var(--text-dim);
            font-size: clamp(0.9375rem, 1.4vw, 1.0625rem);
            line-height: 1.6;
            max-width: 540px;
            margin: 0 auto 2rem;
        }

        /* Form */
        .form {
            display: flex; gap: .5rem;
            width: 100%; max-width: 480px;
            padding: 5px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--hair);
            border-radius: 14px;
            position: relative;
            margin: 0 auto;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .form::before {
            content: ""; position: absolute; inset: -1px; border-radius: 14px;
            padding: 1px; pointer-events: none;
            background: var(--primary-gradient); opacity: 0;
            transition: opacity .25s;
            -webkit-mask: linear-gradient(black, black) content-box, linear-gradient(black, black);
            -webkit-mask-composite: xor; mask-composite: exclude;
        }
        .form:focus-within::before { opacity: 1; }
        .form:focus-within { background: rgba(255, 255, 255, .05); box-shadow: 0 0 24px rgba(0, 240, 255, .35); }
        .form input {
            flex: 1; border: 0; background: transparent;
            color: var(--text); font: inherit; font-size: 0.9375rem;
            padding: .75rem 1rem;
        }
        .form input::placeholder { color: var(--text-faint); }
        .form input:focus { outline: none; }
        .form button {
            border: 0; cursor: pointer;
            background: var(--primary-gradient); background-size: 200% auto;
            color: white; font: 600 0.9375rem/1 inherit;
            padding: 0 1.25rem; border-radius: 9px;
            transition: background-position .3s, transform .15s;
        }
        .form button:hover { background-position: 100% 50%; transform: translateY(-1px); }
        .form-helper { margin: .875rem 0 0; font-size: 0.8125rem; color: var(--text-faint); }
        .form-thanks { display: none; margin: 1rem 0 0; color: var(--cyan); font-weight: 600; }
        .form-error { display: none; margin: .625rem 0 0; color: #ff6b9d; font-size: 0.8125rem; }
        .form.hidden { display: none; }
        .form-thanks.visible, .form-error.visible { display: block; }
        .cs-honeypot { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }

        /* -------- Footer -------- */
        footer.foot {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 3;
            padding: 1rem 1.5rem;
            text-align: center;
            font: 500 0.75rem/1 ui-monospace, monospace;
            color: var(--text-faint);
            letter-spacing: 0.06em;
            border-top: 1px solid var(--hair-soft);
            background: rgba(5, 8, 23, .3);
            backdrop-filter: blur(8px);
        }
        footer.foot a { color: var(--text-faint); transition: color .15s; }
        footer.foot a:hover, footer.foot a.active { color: var(--cyan); }
        footer.foot .sep { color: rgba(140, 180, 255, .15); margin: 0 .5rem; }

        /* -------- Mobile / short-viewport escape hatches -------- */
        @media (max-width: 720px) {
            .hero { padding: 1rem 1rem 8rem; }
            .form input { font-size: 16px; padding: .625rem .875rem; }
            .form button { padding: 0 1rem; font-size: 0.875rem; }
        }
        /* Landscape phones / very short viewports: free the page to scroll
           so nothing gets clipped. */
        @media (max-height: 620px) {
            html, body { height: auto; overflow: auto; }
            .palettes { position: relative; bottom: auto; left: auto; margin: 2rem auto; flex-wrap: wrap; justify-content: center; }
            footer.foot { position: relative; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001s !important; animation-iteration-count: 1 !important; transition-duration: 0.001s !important; }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        // Real themes we ship — signature colors for each. No fake data.
        $themes = [
            ['name' => 'DEFAULT', 'color' => '#10B981'],
            ['name' => 'MINIMAL', 'color' => '#a8a29e'],
            ['name' => 'GALLERY', 'color' => '#f5f5f5'],
            ['name' => 'MENU',    'color' => '#dc2626'],
            ['name' => 'TECH',    'color' => '#2563eb'],
        ];
    @endphp

    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="bg-scanlines"></div>
    <div class="bg-spotlight" id="spotlight"></div>

    {{-- Abstract floating storefront wireframes. Pure decoration — they
         draw themselves on a slow loop, no faux content. --}}
    <div class="wireframe wf-1" aria-hidden="true">
        <svg viewBox="0 0 220 160" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="2" width="216" height="156" rx="8"/>
            <line x1="2" y1="22" x2="218" y2="22"/>
            <circle cx="14" cy="12" r="3"/>
            <circle cx="26" cy="12" r="3"/>
            <circle cx="38" cy="12" r="3"/>
            <rect x="14" y="36" width="192" height="40" rx="4"/>
            <rect x="14" y="84" width="56" height="64" rx="4"/>
            <rect x="82" y="84" width="56" height="64" rx="4"/>
            <rect x="150" y="84" width="56" height="64" rx="4"/>
        </svg>
    </div>
    <div class="wireframe wf-2" aria-hidden="true">
        <svg viewBox="0 0 260 190" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="2" width="256" height="186" rx="8"/>
            <line x1="2" y1="24" x2="258" y2="24"/>
            <circle cx="14" cy="13" r="3"/>
            <circle cx="26" cy="13" r="3"/>
            <circle cx="38" cy="13" r="3"/>
            <rect x="14" y="40" width="112" height="80" rx="4"/>
            <rect x="138" y="40" width="108" height="14" rx="3"/>
            <rect x="138" y="62" width="80" height="10" rx="3"/>
            <rect x="138" y="80" width="60" height="10" rx="3"/>
            <rect x="138" y="100" width="60" height="20" rx="4"/>
            <rect x="14" y="134" width="232" height="40" rx="4"/>
        </svg>
    </div>
    <div class="wireframe wf-3" aria-hidden="true">
        <svg viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="2" width="100" height="146" rx="14"/>
            <line x1="2" y1="22" x2="102" y2="22"/>
            <circle cx="52" cy="12" r="2"/>
            <rect x="10" y="32" width="84" height="20" rx="3"/>
            <rect x="10" y="58" width="60" height="6" rx="2"/>
            <rect x="10" y="72" width="84" height="6" rx="2"/>
            <rect x="10" y="86" width="50" height="6" rx="2"/>
            <rect x="10" y="100" width="84" height="6" rx="2"/>
            <rect x="10" y="118" width="40" height="20" rx="3"/>
        </svg>
    </div>

    <div class="statusbar">
        <div class="left">
            <span><span class="dot"></span> SYSTEM READY</span>
            <span class="sep">·</span>
            <span id="clockOut">--:--:-- UTC</span>
        </div>
        <div class="right">
            @foreach ($languages as $code => $name)
                <a href="/lang/{{ $code }}" class="@if($currentLocale === $code) active @endif">{{ strtoupper($code) }}</a>
                @if(! $loop->last)<span class="sep">/</span>@endif
            @endforeach
        </div>
    </div>

    <section class="hero">
        <div class="lockup-wrap"><a href="/" aria-label="Ganvo"><x-brand-lockup size="lg" /></a></div>

        <div class="eyebrow">{{ $cs['eyebrow'] ?? __('site.marketing.coming_soon.eyebrow') }}</div>

        <h1>
            <span class="reveal" style="animation-delay: 0s">{{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}</span>
            <br>
            <span class="reveal gradient" style="animation-delay: .15s">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
        </h1>

        <p class="lead">{{ $cs['lead'] ?? __('site.marketing.coming_soon.lead') }}</p>

        <form class="form @if(session('signup_status') === 'ok') hidden @endif"
              method="post" action="{{ route('marketing.signup') }}" id="csNotifyForm" novalidate>
            @csrf
            <input class="cs-honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
            <input type="email" name="email" required value="{{ old('email') }}"
                   placeholder="{{ $cs['email_placeholder'] ?? __('site.marketing.coming_soon.email_placeholder') }}"
                   aria-label="{{ $cs['email_placeholder'] ?? __('site.marketing.coming_soon.email_placeholder') }}">
            <button type="submit">{{ $cs['notify_button'] ?? __('site.marketing.coming_soon.notify') }} →</button>
        </form>
        <p class="form-thanks @if(session('signup_status') === 'ok') visible @endif" id="csNotifyThanks">
            ✓ {{ $cs['thanks_message'] ?? __('site.marketing.coming_soon.thanks') }}
        </p>
        <p class="form-error @if(session('signup_error')) visible @endif" id="csNotifyError" role="alert">{{ session('signup_error') }}</p>
        <p class="form-helper">{{ $cs['helper_text'] ?? __('site.marketing.coming_soon.helper') }}</p>
    </section>

    {{-- Theme palette swatches floating bottom-left — these are the
         REAL themes we ship, not fake metrics. --}}
    <div class="palettes" aria-hidden="true">
        @foreach ($themes as $t)
            <span class="palette">
                <span class="swatch" style="background: {{ $t['color'] }}; color: {{ $t['color'] }};"></span>
                {{ $t['name'] }}
            </span>
        @endforeach
    </div>

    <footer class="foot">
        © {{ date('Y') }} GANVO
        <span class="sep">·</span>
        <a href="/preview/coming-soon-v1">← classic version</a>
    </footer>

    <script>
        // Cursor-following spotlight (rAF-throttled)
        const spotlight = document.getElementById('spotlight');
        let rafId = null, pendingMx = 50, pendingMy = 50;
        document.addEventListener('mousemove', (e) => {
            pendingMx = (e.clientX / window.innerWidth) * 100;
            pendingMy = (e.clientY / window.innerHeight) * 100;
            if (rafId) return;
            rafId = requestAnimationFrame(() => {
                spotlight.style.setProperty('--mx', pendingMx + '%');
                spotlight.style.setProperty('--my', pendingMy + '%');
                rafId = null;
            });
        });

        // UTC clock in the status bar
        const clockOut = document.getElementById('clockOut');
        function tick() {
            const d = new Date();
            const hms = [d.getUTCHours(), d.getUTCMinutes(), d.getUTCSeconds()]
                .map(n => String(n).padStart(2, '0')).join(':');
            clockOut.textContent = hms + ' UTC';
        }
        tick(); setInterval(tick, 1000);

        // Notify form — same handler as v1
        (function () {
            const form = document.getElementById('csNotifyForm');
            if (! form) return;
            const thanks = document.getElementById('csNotifyThanks');
            const errorEl = document.getElementById('csNotifyError');
            const button = form.querySelector('button[type="submit"]');
            const input = form.querySelector('input[name="email"]');

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                errorEl.classList.remove('visible'); errorEl.textContent = '';
                button.disabled = true;
                fetch(form.action, {
                    method: 'POST', body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                }).then(r => r.json().then(b => ({ status: r.status, body: b })))
                  .then(({ status, body }) => {
                      if (status >= 200 && status < 300 && body && body.ok) {
                          form.classList.add('hidden');
                          thanks.classList.add('visible');
                      } else {
                          errorEl.textContent = (body && body.message) || @json(__('site.marketing.coming_soon.error_generic'));
                          errorEl.classList.add('visible');
                          button.disabled = false; input.focus();
                      }
                  }).catch(() => {
                      errorEl.textContent = @json(__('site.marketing.coming_soon.error_network'));
                      errorEl.classList.add('visible');
                      button.disabled = false;
                  });
            });
        })();
    </script>
</body>
</html>
