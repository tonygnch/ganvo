<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ __('site.marketing.coming_soon.meta_description') }}">
    {{-- Hint search engines this is a holding page; the real site will replace it. --}}
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563EB;
            --primary-2: #6366F1;
            --primary-3: #38BDF8;
            --primary-soft: color-mix(in srgb, #2563EB 12%, white);
            --primary-strong: color-mix(in srgb, #2563EB 80%, black);
            --bg: #0b1220;
            --bg-soft: #111827;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --text-soft: #64748b;
            --hair: #1f2937;
            --surface: #111827;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; overflow-x: hidden; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text);
            /* Base layer — the floating orbs sit above this, drawn as absolutely-
               positioned divs in .cs-orbs. */
            background: var(--bg);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative;
            isolation: isolate;
        }
        a { color: inherit; text-decoration: none; }

        /* -------- Floating background orbs -------- */
        .cs-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -2;
            overflow: hidden;
        }
        .cs-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.55;
            animation: csFloat 22s ease-in-out infinite;
            will-change: transform;
        }
        .cs-orb.o1 {
            width: 520px; height: 520px;
            top: -180px; left: -120px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
            animation-delay: 0s;
        }
        .cs-orb.o2 {
            width: 460px; height: 460px;
            bottom: -160px; right: -120px;
            background: radial-gradient(circle, var(--primary-2) 0%, transparent 70%);
            animation-delay: -8s;
            animation-duration: 28s;
        }
        .cs-orb.o3 {
            width: 340px; height: 340px;
            top: 50%; left: 60%;
            background: radial-gradient(circle, var(--primary-3) 0%, transparent 70%);
            animation-delay: -14s;
            animation-duration: 32s;
            opacity: 0.35;
        }
        @keyframes csFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(80px, 60px) scale(1.08); }
            66%      { transform: translate(-40px, -80px) scale(0.95); }
        }

        /* -------- Dot grid overlay (very subtle) -------- */
        .cs-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
            background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: radial-gradient(ellipse 70% 80% at 50% 40%, black 35%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 70% 80% at 50% 40%, black 35%, transparent 75%);
        }

        /* -------- Tiny scattered sparkles -------- */
        .cs-stars { position: fixed; inset: 0; pointer-events: none; z-index: -1; }
        .cs-star {
            position: absolute;
            width: 2px; height: 2px;
            background: white;
            border-radius: 50%;
            opacity: 0.4;
            animation: csTwinkle 4s ease-in-out infinite;
        }
        @keyframes csTwinkle {
            0%, 100% { opacity: 0.2; transform: scale(0.6); }
            50%      { opacity: 0.95; transform: scale(1.4); }
        }

        /* -------- Top bar -------- */
        header.cs-bar {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            animation: csFadeDown .8s ease-out .1s both;
        }
        .cs-brand {
            font-weight: 800;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .cs-brand .dot {
            color: var(--primary);
            display: inline-block;
            animation: csBrandPulse 2.4s ease-in-out infinite;
        }
        @keyframes csBrandPulse {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.3); }
        }

        /* ---- Compact language switcher mirroring the storefront/admin pattern ---- */
        .cs-lang { position: relative; }
        .cs-lang summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .375rem .625rem;
            border-radius: 9999px;
            border: 1px solid var(--hair);
            background: rgba(255,255,255,0.04);
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 600;
            user-select: none;
            transition: color .15s ease, background-color .15s ease, border-color .15s ease;
        }
        .cs-lang summary::-webkit-details-marker,
        .cs-lang summary::marker { display: none; content: none; }
        .cs-lang summary:hover { color: var(--text); background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.18); }
        .cs-lang .globe { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 1.4; stroke-linecap: round; }
        .cs-lang .chevron { width: 9px; height: 9px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .15s ease; }
        .cs-lang[open] .chevron { transform: rotate(180deg); }
        .cs-lang-menu {
            position: absolute;
            top: calc(100% + .5rem);
            right: 0;
            min-width: 160px;
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: .625rem;
            padding: .25rem;
            box-shadow: 0 16px 32px -12px rgba(0,0,0,.4);
            z-index: 10;
        }
        .cs-lang-menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem .75rem;
            border-radius: .375rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: background-color .12s ease, color .12s ease;
        }
        .cs-lang-menu a:hover { background: rgba(255,255,255,0.06); color: var(--text); }
        .cs-lang-menu a.active { color: var(--text); font-weight: 600; }
        .cs-lang-menu .check { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .cs-lang-menu a:not(.active) .check { visibility: hidden; }

        main.cs-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        .cs-panel {
            max-width: 760px;
            width: 100%;
            text-align: center;
            position: relative;
        }

        /* -------- Redesigned status pill --------
           Replaces the old simple pulsing-dot eyebrow. The pill itself sits on
           a thin gradient border that slowly rotates via a conic-gradient
           pseudo-element (no JS, no requestAnimationFrame). Three dots on
           the left animate sequentially like a typing indicator to suggest
           "we're still working on it". */
        .cs-pill {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .625rem;
            padding: .5rem 1.125rem;
            border-radius: 9999px;
            background: rgba(11, 18, 32, 0.85);
            color: rgba(255,255,255,0.92);
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin: 0 0 1.75rem;
            backdrop-filter: blur(8px);
            isolation: isolate;
            transition: transform .2s ease;
            animation: csFadeUp .8s ease-out .3s both;
        }
        .cs-pill:hover { transform: translateY(-1px); }

        /* Rotating gradient border ring — sits behind the pill. */
        .cs-pill::before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: 9999px;
            padding: 2px;
            background: conic-gradient(
                from 0deg,
                var(--primary) 0deg,
                var(--primary-3) 90deg,
                var(--primary-2) 180deg,
                var(--primary) 270deg,
                var(--primary) 360deg
            );
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
                    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                    mask-composite: exclude;
            animation: csSpin 6s linear infinite;
            z-index: -1;
        }
        @keyframes csSpin { to { transform: rotate(360deg); } }

        /* Soft halo glow underneath. */
        .cs-pill::after {
            content: "";
            position: absolute;
            inset: -10px;
            border-radius: 9999px;
            background: radial-gradient(circle, color-mix(in srgb, var(--primary) 40%, transparent), transparent 70%);
            filter: blur(14px);
            z-index: -2;
            opacity: 0.6;
            animation: csHaloPulse 3s ease-in-out infinite;
        }
        @keyframes csHaloPulse {
            0%, 100% { opacity: 0.4; transform: scale(0.96); }
            50%      { opacity: 0.85; transform: scale(1.08); }
        }

        .cs-pill .cs-dots {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }
        .cs-pill .cs-dots span {
            width: 5px; height: 5px;
            border-radius: 50%;
            background: var(--primary-3);
            opacity: 0.4;
            animation: csTypingDot 1.4s ease-in-out infinite;
        }
        .cs-pill .cs-dots span:nth-child(1) { animation-delay: 0s; }
        .cs-pill .cs-dots span:nth-child(2) { animation-delay: 0.2s; }
        .cs-pill .cs-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes csTypingDot {
            0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
            30%           { opacity: 1;   transform: translateY(-3px); }
        }

        /* -------- Hero title --------
           Animated linear-gradient shimmer across the text. */
        .cs-title {
            font-size: clamp(2.25rem, 6vw, 4rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.05;
            margin: 0 0 1.25rem;
            background: linear-gradient(
                100deg,
                #ffffff 0%,
                color-mix(in srgb, var(--primary-3) 70%, white) 35%,
                #ffffff 60%,
                color-mix(in srgb, var(--primary) 60%, white) 100%
            );
            background-size: 200% 100%;
            background-position: 0% 50%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
            animation: csFadeUp .9s ease-out .45s both, csTitleShimmer 8s ease-in-out infinite 1.5s;
        }
        @keyframes csTitleShimmer {
            0%, 100% { background-position: 0% 50%; }
            50%      { background-position: 100% 50%; }
        }
        .cs-lead {
            font-size: clamp(1rem, 1.8vw, 1.125rem);
            color: var(--text-muted);
            max-width: 540px;
            margin: 0 auto 2.25rem;
            line-height: 1.6;
            animation: csFadeUp 1s ease-out .65s both;
        }

        /* -------- Email capture form -------- */
        .cs-form {
            display: flex;
            gap: .5rem;
            max-width: 460px;
            margin: 0 auto;
            padding: 6px;
            border: 1px solid var(--hair);
            border-radius: 9999px;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(8px);
            transition: border-color .25s ease, box-shadow .25s ease;
            animation: csFadeUp 1s ease-out .85s both;
        }
        .cs-form:focus-within {
            border-color: color-mix(in srgb, var(--primary) 60%, transparent);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 22%, transparent),
                        0 16px 40px -10px color-mix(in srgb, var(--primary) 40%, transparent);
        }
        .cs-form input {
            flex: 1;
            border: 0;
            background: transparent;
            color: var(--text);
            font: inherit;
            font-size: 0.9375rem;
            padding: .625rem 1rem;
        }
        .cs-form input::placeholder { color: var(--text-soft); }
        .cs-form input:focus { outline: none; }

        /* Submit button — gradient background with an animated shimmer
           sweep on hover (a tilted highlight that travels left-to-right). */
        .cs-form button {
            position: relative;
            overflow: hidden;
            border: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-2) 100%);
            color: white;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: .625rem 1.25rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease;
            box-shadow: 0 8px 24px -8px color-mix(in srgb, var(--primary) 60%, transparent);
        }
        .cs-form button::before {
            content: "";
            position: absolute;
            top: 0; left: -100%;
            width: 60%; height: 100%;
            background: linear-gradient(110deg, transparent 0%, rgba(255,255,255,0.35) 50%, transparent 100%);
            transition: left .5s ease;
        }
        .cs-form button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 32px -8px color-mix(in srgb, var(--primary) 70%, transparent);
        }
        .cs-form button:hover::before { left: 130%; }

        .cs-form-thanks {
            display: none;
            color: color-mix(in srgb, var(--primary-3) 80%, white);
            font-weight: 600;
            margin-top: 1rem;
            animation: csFadeUp .4s ease-out both;
        }
        .cs-form:has(input.submitted) { display: none; }
        .cs-form:has(input.submitted) + .cs-form-thanks { display: block; }

        .cs-helper {
            margin-top: 1.25rem;
            color: var(--text-soft);
            font-size: 0.8125rem;
            animation: csFadeUp 1.1s ease-out 1.05s both;
        }

        footer.cs-foot {
            padding: 2rem;
            text-align: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            animation: csFadeUp 1.2s ease-out 1.3s both;
        }

        /* -------- Entrance keyframes shared across hero pieces -------- */
        @keyframes csFadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes csFadeDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Respect users who'd rather not have decorative motion. Keeps the
           page legible but kills the orbs / shimmer / spinner / typing dots. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001s !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001s !important;
            }
            .cs-orb, .cs-star, .cs-grid { display: none; }
        }
    </style>
</head>
<body>
    {{-- Background layers — fixed, sit behind everything else (z-index: -1/-2). --}}
    <div class="cs-orbs" aria-hidden="true">
        <div class="cs-orb o1"></div>
        <div class="cs-orb o2"></div>
        <div class="cs-orb o3"></div>
    </div>
    <div class="cs-grid" aria-hidden="true"></div>
    <div class="cs-stars" aria-hidden="true">
        @php
            // Deterministic per-request positions so the layout doesn't reflow
            // on hot-reload of the same view. 18 sparkles scattered with
            // randomized delays / sizes.
            $stars = [];
            mt_srand(42);
            for ($i = 0; $i < 18; $i++) {
                $stars[] = [
                    'top'   => mt_rand(2, 95),
                    'left'  => mt_rand(2, 98),
                    'delay' => mt_rand(0, 4000) / 1000,
                    'dur'   => mt_rand(3000, 6000) / 1000,
                    'sz'    => mt_rand(15, 35) / 10,
                ];
            }
            mt_srand();
        @endphp
        @foreach ($stars as $s)
            <span class="cs-star" style="top: {{ $s['top'] }}%; left: {{ $s['left'] }}%; animation-delay: {{ $s['delay'] }}s; animation-duration: {{ $s['dur'] }}s; transform: scale({{ $s['sz'] }});"></span>
        @endforeach
    </div>

    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
    @endphp

    <header class="cs-bar">
        <span class="cs-brand">Ganvo<span class="dot">.</span></span>
        <details class="cs-lang">
            <summary aria-label="{{ __('site.lang.switch') }}">
                <svg class="globe" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18"/>
                    <path d="M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
                </svg>
                <span>{{ strtoupper($currentLocale) }}</span>
                <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true">
                    <path d="M3 4.5L6 7.5L9 4.5"/>
                </svg>
            </summary>
            <div class="cs-lang-menu" role="menu">
                @foreach ($languages as $code => $name)
                    <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale === $code) active @endif">
                        <span>{{ $name }}</span>
                        <svg class="check" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M4 10l4 4 8-8"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </details>
    </header>

    <main class="cs-stage">
        <div class="cs-panel">
            <div class="cs-pill" role="status" aria-live="polite">
                <span class="cs-dots" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                {{ __('site.marketing.coming_soon.eyebrow') }}
            </div>
            <h1 class="cs-title">{{ __('site.marketing.coming_soon.title') }}</h1>
            <p class="cs-lead">{{ __('site.marketing.coming_soon.lead') }}</p>

            <form class="cs-form"
                  onsubmit="event.preventDefault(); this.querySelector('input').classList.add('submitted'); this.querySelector('input').setAttribute('readonly','');">
                <input type="email" required placeholder="{{ __('site.marketing.coming_soon.email_placeholder') }}">
                <button type="submit">{{ __('site.marketing.coming_soon.notify') }}</button>
            </form>
            <p class="cs-form-thanks">✓ {{ __('site.marketing.coming_soon.thanks') }}</p>

            <p class="cs-helper">{{ __('site.marketing.coming_soon.helper') }}</p>
        </div>
    </main>

    <footer class="cs-foot">
        © {{ date('Y') }} Ganvo
    </footer>
</body>
</html>
