<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">

    {{-- Set theme before paint, same approach the main marketing page uses. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('ganvo-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', stored || (prefersDark ? 'dark' : 'light'));
        })();
    </script>

    <style>
        /* -------- Palette: shared with marketing/home.blade.php so the
                    "Launching soon" splash feels like the same product. -------- */
        :root, [data-theme="light"] {
            --bg:           #ffffff;
            --bg-elevated:  #ffffff;
            --bg-muted:     #f8fafc;
            --bg-subtle:    #f1f5f9;
            --border:       #e2e8f0;
            --border-strong:#cbd5e1;
            --text:         #0f172a;
            --text-muted:   #475569;
            --text-soft:    #64748b;
            --brand:        #2563eb;
            --brand-hover:  #1d4ed8;
            --brand-soft:   #eff6ff;
            --accent:       #0ea5e9;
            --accent-3:     #06b6d4;
            --dot-color:    rgba(37, 99, 235, 0.22);
            --shape-stroke: rgba(37, 99, 235, 0.30);
            --gradient-cta: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #06b6d4 100%);
            --card-shadow:  0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04);
        }
        [data-theme="dark"] {
            --bg:           #0b1020;
            --bg-elevated:  #131a2f;
            --bg-muted:     #0f1426;
            --bg-subtle:    #1a2240;
            --border:       #1f2942;
            --border-strong:#2a3556;
            --text:         #e7ecf5;
            --text-muted:   #a3aac4;
            --text-soft:    #818bab;
            --brand:        #60a5fa;
            --brand-hover:  #93c5fd;
            --brand-soft:   #16224a;
            --accent:       #38bdf8;
            --accent-3:     #22d3ee;
            --dot-color:    rgba(96, 165, 250, 0.28);
            --shape-stroke: rgba(96, 165, 250, 0.45);
            --gradient-cta: linear-gradient(135deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%);
            --card-shadow:  0 1px 2px rgba(0,0,0,0.4), 0 4px 12px rgba(0,0,0,0.25);
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color .25s ease, color .25s ease;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: none; }

        /* -------- Nav -------- */
        nav.cs-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: color-mix(in srgb, var(--bg) 80%, transparent);
            backdrop-filter: saturate(180%) blur(10px);
            border-bottom: 1px solid var(--border);
        }
        .cs-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .cs-brand {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-weight: 800;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .cs-brand .dot { color: var(--brand); }
        .cs-nav-right { display: flex; align-items: center; gap: .75rem; }

        /* Language switcher chip — mirrors the marketing nav's variant. */
        .lang-menu { position: relative; }
        .lang-menu summary {
            list-style: none;
            cursor: pointer;
            padding: .5rem .75rem;
            border-radius: 9999px;
            border: 1px solid var(--border);
            background: var(--bg-subtle);
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            user-select: none;
            transition: color .15s ease, border-color .15s ease, background-color .15s ease;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { color: var(--text); border-color: var(--border-strong); }
        .lang-menu .globe { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 1.4; stroke-linecap: round; }
        .lang-menu .chevron { width: 9px; height: 9px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .15s ease; }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute;
            top: calc(100% + .5rem);
            right: 0;
            min-width: 160px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: .625rem;
            padding: .25rem;
            box-shadow: var(--card-shadow);
            z-index: 10;
        }
        .lang-menu-items a {
            display: flex; align-items: center; justify-content: space-between;
            padding: .5rem .75rem;
            border-radius: .375rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: background-color .12s ease, color .12s ease;
        }
        .lang-menu-items a:hover { background: var(--bg-subtle); color: var(--text); }
        .lang-menu-items a.active { color: var(--brand); font-weight: 600; }
        .lang-menu-items .check { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }

        .theme-toggle {
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 9999px;
            width: 36px; height: 36px;
            display: inline-flex;
            align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            transition: color .15s ease, transform .15s ease, border-color .15s ease;
        }
        .theme-toggle:hover { color: var(--text); border-color: var(--border-strong); transform: rotate(15deg); }
        .theme-toggle .sun, .theme-toggle .moon { font-size: 16px; line-height: 1; }
        [data-theme="light"] .theme-toggle .moon,
        [data-theme="dark"] .theme-toggle .sun { display: none; }

        /* -------- Hero -------- */
        .cs-hero {
            flex: 1;
            position: relative;
            overflow: hidden;
            min-height: calc(100vh - 72px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 1.5rem;
            background:
                radial-gradient(ellipse 80% 60% at 50% 0%, var(--brand-soft), transparent 60%),
                radial-gradient(circle 700px at 90% 110%, color-mix(in srgb, var(--accent) 12%, transparent), transparent 60%);
        }
        /* Soft dot pattern — same vocabulary as the marketing hero. */
        .cs-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--dot-color) 1px, transparent 1px);
            background-size: 22px 22px;
            mask-image: radial-gradient(ellipse 60% 60% at 50% 50%, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse 60% 60% at 50% 50%, black 30%, transparent 70%);
            pointer-events: none;
            opacity: .9;
        }
        .cs-hero-inner {
            position: relative;
            text-align: center;
            max-width: 720px;
            width: 100%;
            z-index: 2;
        }

        .cs-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .375rem .875rem;
            border-radius: 9999px;
            background: var(--brand-soft);
            color: var(--brand);
            border: 1px solid color-mix(in srgb, var(--brand) 25%, transparent);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 0 0 1.25rem;
        }
        .cs-pill .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 0 color-mix(in srgb, var(--brand) 60%, transparent);
            animation: csPulse 1.8s ease-out infinite;
        }
        @keyframes csPulse {
            0%   { box-shadow: 0 0 0 0 color-mix(in srgb, var(--brand) 60%, transparent); }
            70%  { box-shadow: 0 0 0 8px transparent; }
            100% { box-shadow: 0 0 0 0 transparent; }
        }

        .cs-hero h1 {
            font-size: clamp(2.25rem, 5.5vw, 3.75rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.1;
            margin: 0 0 1rem;
        }
        .cs-hero h1 .accent {
            background: var(--gradient-cta);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .cs-hero p.lead {
            font-size: clamp(1rem, 1.6vw, 1.125rem);
            color: var(--text-muted);
            max-width: 540px;
            margin: 0 auto 2rem;
        }

        /* -------- Email capture form — pill input + gradient CTA matching marketing -------- */
        .cs-form {
            display: flex;
            gap: .5rem;
            max-width: 480px;
            margin: 0 auto;
            padding: 6px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            border-radius: 9999px;
            box-shadow: var(--card-shadow);
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .cs-form:focus-within {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-soft);
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
        .cs-form button {
            border: 0;
            background: var(--gradient-cta);
            color: white;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: .625rem 1.25rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.45);
        }
        .cs-form button:hover { transform: translateY(-1px); filter: brightness(1.05); box-shadow: 0 10px 22px -6px rgba(37, 99, 235, 0.55); }

        .cs-form-thanks {
            display: none;
            color: var(--brand);
            font-weight: 600;
            margin-top: 1rem;
        }
        .cs-form:has(input.submitted) { display: none; }
        .cs-form:has(input.submitted) + .cs-form-thanks { display: block; }

        .cs-helper { margin-top: 1rem; color: var(--text-soft); font-size: 0.8125rem; }

        /* -------- macOS browser mockup -- lifted from marketing/home.blade.php
                    so this page reads as part of the same product family. -------- */
        .cs-browser {
            position: absolute;
            top: 6%;
            right: 4%;
            width: 280px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 18px 40px -12px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            transform: rotate(-4deg);
            pointer-events: none;
            animation: cs-browser-drift 12s ease-in-out infinite;
            z-index: 1;
        }
        [data-theme="dark"] .cs-browser {
            box-shadow: 0 18px 40px -12px rgba(0, 0, 0, 0.55), 0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .cs-browser-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: var(--bg-muted);
            border-bottom: 1px solid var(--border);
        }
        .cs-browser-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .cs-browser-dot.red    { background: #ff5f57; }
        .cs-browser-dot.yellow { background: #febc2e; }
        .cs-browser-dot.green  { background: #28c840; }
        .cs-url-pill {
            flex: 1;
            margin-left: 10px;
            height: 16px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 6px;
            color: var(--text-soft);
            font: 500 9px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: 0.02em;
            overflow: hidden;
            white-space: nowrap;
        }
        .cs-browser-content {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .cs-browser-hero {
            height: 36px;
            border-radius: 6px;
            background: var(--gradient-cta);
            background-size: 200% auto;
            animation: cs-gradient-shift 6s linear infinite;
        }
        .cs-browser-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .cs-browser-tile { aspect-ratio: 1; background: var(--bg-subtle); border-radius: 6px; }
        .cs-browser-line { height: 6px; border-radius: 3px; background: var(--bg-subtle); }
        .cs-browser-line.short { width: 55%; }
        @keyframes cs-browser-drift {
            0%, 100% { transform: rotate(-4deg) translateY(0); }
            50%      { transform: rotate(-4deg) translateY(-12px); }
        }
        @keyframes cs-gradient-shift {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        /* -------- Phone mockup -- same provenance as the browser frame above. -------- */
        .cs-phone {
            position: absolute;
            bottom: 8%;
            left: 4%;
            width: 130px;
            height: 250px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 6px;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
            transform: rotate(6deg);
            pointer-events: none;
            animation: cs-phone-drift 14s ease-in-out infinite;
            animation-delay: -2s;
            z-index: 1;
        }
        [data-theme="dark"] .cs-phone {
            box-shadow: 0 18px 36px -12px rgba(0, 0, 0, 0.55), 0 4px 10px -2px rgba(0, 0, 0, 0.4);
        }
        .cs-phone-screen {
            width: 100%;
            height: 100%;
            background: var(--bg);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 8px 10px;
            gap: 6px;
            position: relative;
        }
        .cs-phone-notch {
            width: 36px;
            height: 4px;
            background: var(--border-strong);
            border-radius: 999px;
            margin: 2px auto 4px;
        }
        .cs-phone-title {
            font-family: Georgia, serif;
            font-style: italic;
            color: var(--text);
            text-align: center;
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }
        .cs-phone-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9px;
            color: var(--text-muted);
            padding: 4px 0;
            border-bottom: 1px solid var(--border);
        }
        .cs-phone-row:last-child { border-bottom: 0; }
        .cs-phone-row .name {
            flex: 1;
            height: 6px;
            background: var(--bg-subtle);
            border-radius: 3px;
            margin-right: 8px;
        }
        .cs-phone-row .name.short { max-width: 40%; }
        .cs-phone-row .price {
            font: 600 8px ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--text-soft);
        }
        @keyframes cs-phone-drift {
            0%, 100% { transform: rotate(6deg) translateY(0); }
            50%      { transform: rotate(6deg) translateY(-10px); }
        }

        /* Hide both mockups on narrow viewports — same rule the marketing
           hero uses, so we never overlap the headline copy. */
        @media (max-width: 900px) {
            .cs-browser, .cs-phone { display: none; }
        }

        /* -------- Footer -------- */
        footer.cs-foot {
            padding: 1.5rem;
            text-align: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            border-top: 1px solid var(--border);
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.001s !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.001s !important;
            }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $brandedHost = str_replace(':8000', '', config('ganvo.central_domain'));
    @endphp

    <nav class="cs-nav">
        <div class="cs-nav-inner">
            <a href="/" class="cs-brand">Ganvo<span class="dot">.</span></a>
            <div class="cs-nav-right">
                <details class="lang-menu">
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
                    <div class="lang-menu-items" role="menu">
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
                <button class="theme-toggle" type="button" aria-label="Toggle theme" id="csThemeToggle">
                    <span class="sun" aria-hidden="true">☀</span>
                    <span class="moon" aria-hidden="true">☾</span>
                </button>
            </div>
        </div>
    </nav>

    <section class="cs-hero">
        {{-- Decorative storefront preview floating in the top-right --}}
        <div class="cs-browser" aria-hidden="true">
            <div class="cs-browser-bar">
                <span class="cs-browser-dot red"></span>
                <span class="cs-browser-dot yellow"></span>
                <span class="cs-browser-dot green"></span>
                <div class="cs-url-pill">acme.{{ $brandedHost }}</div>
            </div>
            <div class="cs-browser-content">
                <div class="cs-browser-hero"></div>
                <div class="cs-browser-grid">
                    <div class="cs-browser-tile"></div>
                    <div class="cs-browser-tile"></div>
                    <div class="cs-browser-tile"></div>
                </div>
                <div class="cs-browser-line"></div>
                <div class="cs-browser-line short"></div>
            </div>
        </div>

        {{-- Mobile storefront mock floating in the bottom-left --}}
        <div class="cs-phone" aria-hidden="true">
            <div class="cs-phone-screen">
                <div class="cs-phone-notch"></div>
                <div class="cs-phone-title">Aurora</div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$29</span></div>
                <div class="cs-phone-row"><span class="name short"></span><span class="price">$48</span></div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$74</span></div>
                <div class="cs-phone-row"><span class="name short"></span><span class="price">$19</span></div>
                <div class="cs-phone-row"><span class="name"></span><span class="price">$32</span></div>
            </div>
        </div>

        <div class="cs-hero-inner">
            <div class="cs-pill">
                <span class="pulse" aria-hidden="true"></span>
                {{ __('site.marketing.coming_soon.eyebrow') }}
            </div>
            <h1>
                {{ __('site.marketing.coming_soon.headline_1') }}
                <br>
                <span class="accent">{{ __('site.marketing.coming_soon.headline_2') }}</span>
            </h1>
            <p class="lead">{{ __('site.marketing.coming_soon.lead') }}</p>

            <form class="cs-form"
                  onsubmit="event.preventDefault(); var i = this.querySelector('input'); i.classList.add('submitted'); i.setAttribute('readonly','');">
                <input type="email" required placeholder="{{ __('site.marketing.coming_soon.email_placeholder') }}">
                <button type="submit">{{ __('site.marketing.coming_soon.notify') }}</button>
            </form>
            <p class="cs-form-thanks">✓ {{ __('site.marketing.coming_soon.thanks') }}</p>

            <p class="cs-helper">{{ __('site.marketing.coming_soon.helper') }}</p>
        </div>
    </section>

    <footer class="cs-foot">
        © {{ date('Y') }} Ganvo
    </footer>

    <script>
        // Theme toggle — same shape as the marketing-page version so the
        // preference roundtrips cleanly when the visitor lands on the real
        // site once it's live.
        document.getElementById('csThemeToggle').addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('ganvo-theme', next); } catch (e) { /* private mode */ }
        });
    </script>
</body>
</html>
