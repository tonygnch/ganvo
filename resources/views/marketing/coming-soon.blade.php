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
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text);
            background:
                radial-gradient(1200px 700px at 20% -10%, color-mix(in srgb, var(--primary) 28%, transparent), transparent 60%),
                radial-gradient(900px 600px at 110% 110%, color-mix(in srgb, var(--primary) 18%, transparent), transparent 65%),
                var(--bg);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }

        header.cs-bar {
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .cs-brand {
            font-weight: 800;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
            color: var(--text);
        }
        .cs-brand .dot { color: var(--primary); }

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
            transition: color .15s ease, background-color .15s ease;
        }
        .cs-lang summary::-webkit-details-marker,
        .cs-lang summary::marker { display: none; content: none; }
        .cs-lang summary:hover { color: var(--text); background: rgba(255,255,255,0.08); }
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
        }
        .cs-panel {
            max-width: 720px;
            width: 100%;
            text-align: center;
        }
        .cs-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .375rem .875rem;
            border-radius: 9999px;
            border: 1px solid color-mix(in srgb, var(--primary) 40%, transparent);
            background: color-mix(in srgb, var(--primary) 14%, transparent);
            color: color-mix(in srgb, var(--primary) 90%, white);
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin: 0 0 1.75rem;
        }
        .cs-eyebrow .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 0 0 rgba(37, 99, 235, .6);
            animation: csPulse 1.8s ease-out infinite;
        }
        @keyframes csPulse {
            0%   { box-shadow: 0 0 0 0 rgba(37, 99, 235, .6); }
            70%  { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }
        .cs-title {
            font-size: clamp(2.25rem, 6vw, 4rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.05;
            margin: 0 0 1.25rem;
            background: linear-gradient(180deg, #ffffff 0%, color-mix(in srgb, var(--primary) 28%, #ffffff) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .cs-lead {
            font-size: clamp(1rem, 1.8vw, 1.125rem);
            color: var(--text-muted);
            max-width: 540px;
            margin: 0 auto 2.25rem;
            line-height: 1.6;
        }

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
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: .625rem 1.25rem;
            border-radius: 9999px;
            cursor: pointer;
            transition: background-color .15s ease, transform .12s ease;
        }
        .cs-form button:hover { background: var(--primary-strong); transform: translateY(-1px); }

        .cs-form-thanks {
            display: none;
            color: color-mix(in srgb, var(--primary) 80%, white);
            font-weight: 600;
            margin-top: 1rem;
        }
        .cs-form:has(input.submitted) { display: none; }
        .cs-form:has(input.submitted) + .cs-form-thanks { display: block; }

        .cs-helper {
            margin-top: 1.25rem;
            color: var(--text-soft);
            font-size: 0.8125rem;
        }

        footer.cs-foot {
            padding: 2rem;
            text-align: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            letter-spacing: 0.06em;
        }
    </style>
</head>
<body>
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
            <p class="cs-eyebrow">
                <span class="pulse" aria-hidden="true"></span>
                {{ __('site.marketing.coming_soon.eyebrow') }}
            </p>
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
