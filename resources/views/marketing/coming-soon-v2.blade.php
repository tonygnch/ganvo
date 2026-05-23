<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <title>{{ $cs['page_title'] ?? __('site.marketing.coming_soon.title') }} — Ganvo</title>
    <meta name="description" content="{{ $cs['meta_description'] ?? __('site.marketing.coming_soon.meta_description') }}">
    <meta name="robots" content="noindex, nofollow">

    {{-- Dark-only design — no theme toggle on v2. The aesthetic depends on a deep background. --}}
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
            --green:     #00ff88;
            --primary-gradient: linear-gradient(135deg, var(--cyan), var(--violet) 50%, var(--magenta));
            --primary-glow: 0 0 24px rgba(0, 240, 255, .35), 0 0 48px rgba(255, 45, 208, .25);
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100vh; min-height: 100dvh; background: var(--bg-deep); color: var(--text); font-family: ui-sans-serif, system-ui, -apple-system, 'Inter', sans-serif; overflow-x: hidden; }
        a { color: var(--cyan); text-decoration: none; }
        body { position: relative; }

        /* -------- Background layer 1: animated mesh gradient -------- */
        .bg-mesh {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
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

        /* -------- Background layer 2: grid + scan lines -------- */
        .bg-grid {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background-image:
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
            -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 40%, transparent 85%);
        }
        .bg-scanlines {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: .35;
            background-image: repeating-linear-gradient(
                0deg,
                rgba(255, 255, 255, .015) 0px,
                rgba(255, 255, 255, .015) 1px,
                transparent 1px,
                transparent 3px
            );
        }

        /* -------- Background layer 3: cursor-following spotlight -------- */
        .bg-spotlight {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background: radial-gradient(circle 400px at var(--mx, 50%) var(--my, 50%), rgba(0, 240, 255, .08), transparent 70%);
            transition: background .1s ease;
        }

        /* -------- Top status bar -------- */
        .statusbar {
            position: relative; z-index: 3;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--hair-soft);
            font: 500 0.6875rem/1 ui-monospace, 'JetBrains Mono', SFMono-Regular, monospace;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-faint);
            backdrop-filter: blur(8px);
            background: rgba(5, 8, 23, .3);
        }
        .statusbar .left  { display: flex; gap: 1.5rem; align-items: center; }
        .statusbar .right { display: flex; gap: 1rem; align-items: center; }
        .statusbar .sep   { color: rgba(140, 180, 255, .15); }
        .statusbar .dot   { width: 6px; height: 6px; border-radius: 50%; background: var(--green); box-shadow: 0 0 8px var(--green); animation: livePulse 2s ease-in-out infinite; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .statusbar a { color: var(--text-faint); transition: color .15s; }
        .statusbar a:hover { color: var(--cyan); }
        .statusbar a.active { color: var(--text); }

        /* -------- Hero -------- */
        .hero {
            position: relative; z-index: 2;
            max-width: 1280px; margin: 0 auto;
            padding: 4rem 1.5rem 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            min-height: calc(100dvh - 100px);
        }
        @media (max-width: 920px) { .hero { grid-template-columns: 1fr; padding: 2.5rem 1.25rem 3rem; gap: 2rem; min-height: auto; } }

        /* Left column: text + form */
        .lockup-row { margin: 0 0 2rem; }
        .lockup-row img { filter: drop-shadow(0 0 24px rgba(0, 240, 255, .25)); }

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
            margin: 0 0 1.5rem;
        }
        .eyebrow::before {
            content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--cyan);
            box-shadow: 0 0 8px var(--cyan); animation: livePulse 1.5s ease-in-out infinite;
        }

        h1 {
            font-size: clamp(2.5rem, 5.5vw, 4.5rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
            margin: 0 0 1.25rem;
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
        /* Subtle character reveal on initial load */
        h1 .reveal {
            display: inline-block;
            opacity: 0; transform: translateY(8px);
            animation: revealUp .6s ease forwards;
        }
        @keyframes revealUp { to { opacity: 1; transform: translateY(0); } }

        .lead {
            color: var(--text-dim);
            font-size: 1.0625rem;
            line-height: 1.6;
            max-width: 480px;
            margin: 0 0 2rem;
        }

        /* Form */
        .form {
            display: flex; gap: .5rem;
            max-width: 480px;
            padding: 5px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--hair);
            border-radius: 14px;
            transition: border-color .2s, box-shadow .2s, background .2s;
            position: relative;
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
        .form:focus-within { background: rgba(255, 255, 255, .05); box-shadow: var(--primary-glow); }
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
            padding: 0 1.5rem; border-radius: 9px;
            transition: background-position .3s, transform .15s;
        }
        .form button:hover { background-position: 100% 50%; transform: translateY(-1px); }
        .form-helper { margin: .875rem 0 0; font-size: 0.8125rem; color: var(--text-faint); }
        .form-thanks { display: none; margin: 1rem 0 0; color: var(--cyan); font-weight: 600; }
        .form-error { display: none; margin: .625rem 0 0; color: #ff6b9d; font-size: 0.8125rem; }
        .form.hidden { display: none; }
        .form-thanks.visible, .form-error.visible { display: block; }
        .cs-honeypot { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }

        /* -------- Right column: stage with floating mockups + ticker -------- */
        .stage {
            position: relative;
            height: 540px;
            perspective: 1200px;
        }
        @media (max-width: 920px) { .stage { height: 380px; margin-top: 1rem; } }
        @media (max-width: 600px) { .stage { height: 320px; } }

        .browser, .phone {
            position: absolute;
            border-radius: 14px;
            overflow: hidden;
            background: rgba(15, 20, 45, .85);
            border: 1px solid var(--hair);
            backdrop-filter: blur(12px);
            box-shadow:
                0 30px 60px -20px rgba(0, 0, 0, .6),
                0 0 0 1px rgba(120, 180, 255, .04),
                inset 0 1px 0 rgba(255, 255, 255, .04);
            transform-style: preserve-3d;
        }
        .browser .bar {
            display: flex; align-items: center; gap: 6px; padding: 8px 10px;
            border-bottom: 1px solid var(--hair-soft);
            background: rgba(8, 12, 28, .6);
        }
        .browser .dot { width: 8px; height: 8px; border-radius: 50%; opacity: .7; }
        .browser .dot:nth-child(1) { background: #ff5f57; }
        .browser .dot:nth-child(2) { background: #febc2e; }
        .browser .dot:nth-child(3) { background: #28c840; }
        .browser .url {
            flex: 1; height: 16px; margin-left: 8px; padding: 0 8px;
            background: rgba(0, 0, 0, .35);
            border: 1px solid var(--hair-soft);
            border-radius: 4px;
            display: flex; align-items: center;
            font: 500 9px/1 ui-monospace, monospace;
            color: var(--text-faint);
        }

        /* Browser 1 — top right, large, default theme storefront */
        .b1 {
            top: 0; right: 0; width: 320px;
            transform: rotate(-3deg);
            animation: float1 14s ease-in-out infinite;
        }
        .b1 .preview { padding: 12px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .b1 .preview .hero-tile {
            grid-column: 1 / -1; height: 56px; border-radius: 6px;
            background: linear-gradient(120deg, var(--cyan), var(--violet)); opacity: .7;
        }
        .b1 .preview .card { aspect-ratio: 3/4; background: rgba(255, 255, 255, .04); border-radius: 6px; }
        .b1 .preview .card::after {
            content: ""; display: block; height: 6px; margin: 6px;
            background: rgba(255, 255, 255, .08); border-radius: 2px;
            transform: translateY(calc(100% - 6px - 6px));
        }

        /* Browser 2 — middle right, smaller, dashboard look */
        .b2 {
            top: 30%; right: 35%; width: 240px;
            transform: rotate(4deg);
            animation: float2 18s ease-in-out infinite;
            animation-delay: -3s;
        }
        .b2 .preview { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
        .b2 .preview .stat-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
        .b2 .preview .stat {
            background: rgba(255, 255, 255, .04); border-radius: 5px; padding: 6px;
        }
        .b2 .preview .stat .num { height: 8px; width: 60%; background: var(--cyan); border-radius: 2px; box-shadow: 0 0 6px var(--cyan); }
        .b2 .preview .stat .lbl { height: 4px; width: 80%; background: rgba(255, 255, 255, .1); border-radius: 2px; margin-top: 4px; }
        .b2 .preview .chart {
            height: 60px; background:
                linear-gradient(180deg, transparent, rgba(0, 240, 255, .15) 100%),
                linear-gradient(90deg,
                    rgba(0, 240, 255, .5) 0px,
                    rgba(0, 240, 255, .5) 1px,
                    transparent 1px,
                    transparent 20px);
            border-radius: 4px;
            position: relative;
        }
        .b2 .preview .chart::after {
            content: ""; position: absolute; left: 8px; right: 8px; bottom: 20%;
            height: 1px; background: var(--cyan); box-shadow: 0 0 6px var(--cyan);
            clip-path: polygon(0 50%, 15% 30%, 30% 60%, 45% 20%, 60% 45%, 75% 15%, 100% 35%, 100% 100%, 0 100%);
        }

        /* Phone — bottom left of stage, vertical storefront */
        .phone {
            bottom: 0; left: 0; width: 150px; height: 290px;
            transform: rotate(-5deg);
            animation: float3 16s ease-in-out infinite;
            animation-delay: -6s;
            padding: 8px;
        }
        .phone .screen {
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, .4);
            border-radius: 14px;
            padding: 10px 8px 8px;
            display: flex; flex-direction: column; gap: 6px;
            overflow: hidden;
        }
        .phone .notch { width: 30px; height: 4px; background: rgba(255, 255, 255, .15); border-radius: 999px; margin: 0 auto 4px; }
        .phone .title {
            font: italic 600 9px Georgia, serif; text-align: center;
            color: var(--text-dim); padding-bottom: 4px;
            border-bottom: 1px solid var(--hair-soft); letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .phone .item { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid var(--hair-soft); font: 500 8px ui-monospace, monospace; color: var(--text-dim); }
        .phone .item:last-child { border: 0; }
        .phone .item .name { flex: 1; height: 5px; background: rgba(255, 255, 255, .12); border-radius: 2px; margin-right: 6px; align-self: center; }
        .phone .item .name.s { max-width: 60%; }
        .phone .item .price { color: var(--cyan); }

        /* Floating mini cards — extra atmosphere */
        .mini-card {
            position: absolute;
            background: rgba(15, 20, 45, .8);
            border: 1px solid var(--hair);
            backdrop-filter: blur(8px);
            border-radius: 10px;
            padding: 10px 12px;
            font: 500 11px/1.4 ui-monospace, monospace;
            color: var(--text-dim);
            min-width: 140px;
            box-shadow: 0 8px 24px -8px rgba(0, 0, 0, .5);
        }
        .mini-card .label { color: var(--text-faint); font-size: 9px; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px; }
        .mini-card .value { color: var(--text); font-weight: 700; font-size: 14px; }
        .mini-card .value .cyan { color: var(--cyan); }
        .mini-card .value .green { color: var(--green); }
        .m1 { bottom: 32%; left: 40%; transform: rotate(2deg); animation: float2 12s ease-in-out infinite; animation-delay: -2s; }
        .m2 { top: 40%; left: 8%; transform: rotate(-4deg); animation: float1 15s ease-in-out infinite; animation-delay: -4s; }

        @keyframes float1 {
            0%, 100% { transform: rotate(-3deg) translateY(0); }
            50%      { transform: rotate(-3deg) translateY(-14px); }
        }
        @keyframes float2 {
            0%, 100% { transform: rotate(4deg) translateY(0); }
            50%      { transform: rotate(4deg) translateY(-12px); }
        }
        @keyframes float3 {
            0%, 100% { transform: rotate(-5deg) translateY(0); }
            50%      { transform: rotate(-5deg) translateY(-10px); }
        }

        /* Hide busier stage stuff on small screens — keep only one browser visible */
        @media (max-width: 920px) {
            .b2, .phone, .m1 { display: none; }
            .b1 { width: 100%; max-width: 360px; right: auto; left: 50%; transform: translateX(-50%) rotate(-2deg); top: 0; }
            .m2 { right: 0; left: auto; top: auto; bottom: 0; }
        }
        @media (max-width: 600px) {
            .m2 { display: none; }
        }

        /* -------- Bottom: live activity ticker -------- */
        .ticker-strip {
            position: relative; z-index: 2;
            border-top: 1px solid var(--hair-soft);
            border-bottom: 1px solid var(--hair-soft);
            background: rgba(5, 8, 23, .5);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            overflow: hidden;
            font: 500 0.8125rem/1 ui-monospace, monospace;
            color: var(--text-dim);
            mask-image: linear-gradient(90deg, transparent, black 8%, black 92%, transparent);
            -webkit-mask-image: linear-gradient(90deg, transparent, black 8%, black 92%, transparent);
        }
        .ticker {
            display: flex; gap: 3rem;
            white-space: nowrap;
            animation: tickerScroll 40s linear infinite;
        }
        .ticker .item { display: inline-flex; align-items: center; gap: .625rem; }
        .ticker .item .pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--green); box-shadow: 0 0 6px var(--green); }
        .ticker .item .from { color: var(--text-faint); }
        .ticker .item .amount { color: var(--cyan); font-weight: 700; }
        @keyframes tickerScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* -------- Stats row -------- */
        .stats {
            position: relative; z-index: 2;
            max-width: 1280px; margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;
        }
        @media (max-width: 920px) { .stats { grid-template-columns: repeat(2, 1fr); padding-bottom: 6rem; } }
        @media (max-width: 480px) { .stats { grid-template-columns: 1fr 1fr; gap: 1rem; } }
        .stat-card {
            padding: 1.5rem;
            background: rgba(255, 255, 255, .02);
            border: 1px solid var(--hair);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(circle 200px at 50% 0%, rgba(0, 240, 255, .08), transparent);
            opacity: 0; transition: opacity .3s;
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-card .label { font: 600 0.6875rem/1 ui-monospace, monospace; color: var(--text-faint); letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: .625rem; }
        .stat-card .value {
            font-size: clamp(1.5rem, 3.5vw, 2.25rem);
            font-weight: 800;
            background: var(--primary-gradient); background-clip: text; -webkit-background-clip: text; color: transparent;
            background-size: 200% auto; animation: gradientSlide 8s linear infinite;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .stat-card .delta { margin-top: .5rem; font: 500 0.75rem/1 ui-monospace, monospace; color: var(--green); }

        /* -------- Footer -------- */
        footer.foot {
            position: relative; z-index: 2;
            border-top: 1px solid var(--hair-soft);
            padding: 1.25rem 1.5rem;
            text-align: center;
            font: 500 0.75rem/1 ui-monospace, monospace;
            color: var(--text-faint);
            letter-spacing: 0.06em;
        }
        footer.foot a { color: var(--text-faint); transition: color .15s; }
        footer.foot a:hover, footer.foot a.active { color: var(--cyan); }
        footer.foot .sep { color: rgba(140, 180, 255, .15); margin: 0 .5rem; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001s !important; animation-iteration-count: 1 !important; transition-duration: 0.001s !important; }
        }
    </style>
</head>
<body>
    @php
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $brandedHost = str_replace(':8000', '', config('ganvo.central_domain'));
    @endphp

    <div class="bg-mesh"></div>
    <div class="bg-grid"></div>
    <div class="bg-scanlines"></div>
    <div class="bg-spotlight" id="spotlight"></div>

    <div class="statusbar">
        <div class="left">
            <span><span class="dot"></span> SYSTEM ONLINE</span>
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
        {{-- Left column: text + form --}}
        <div>
            <div class="lockup-row">
                <a href="/" aria-label="Ganvo"><x-brand-lockup size="md" /></a>
            </div>

            <div class="eyebrow">{{ $cs['eyebrow'] ?? __('site.marketing.coming_soon.eyebrow') }}</div>

            <h1>
                <span class="reveal" style="animation-delay: 0s">{{ $cs['headline_1'] ?? __('site.marketing.coming_soon.headline_1') }}</span><br>
                <span class="reveal gradient" style="animation-delay: .15s">{{ $cs['headline_2'] ?? __('site.marketing.coming_soon.headline_2') }}</span>
            </h1>

            <p class="lead">{{ $cs['lead'] ?? __('site.marketing.coming_soon.lead') }}</p>

            <form class="form @if(session('signup_status') === 'ok') hidden @endif"
                  method="post" action="{{ route('marketing.signup') }}"
                  id="csNotifyForm" novalidate>
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
        </div>

        {{-- Right column: stage with floating store mockups + metric cards --}}
        <div class="stage" aria-hidden="true">
            {{-- Browser 1: storefront preview --}}
            <div class="browser b1">
                <div class="bar">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    <div class="url">aurora.{{ $brandedHost }}</div>
                </div>
                <div class="preview">
                    <div class="hero-tile"></div>
                    <div class="card"></div><div class="card"></div><div class="card"></div>
                    <div class="card"></div><div class="card"></div><div class="card"></div>
                </div>
            </div>

            {{-- Browser 2: dashboard preview --}}
            <div class="browser b2">
                <div class="bar">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    <div class="url">dash · acme</div>
                </div>
                <div class="preview">
                    <div class="stat-row">
                        <div class="stat"><div class="num"></div><div class="lbl"></div></div>
                        <div class="stat"><div class="num"></div><div class="lbl"></div></div>
                        <div class="stat"><div class="num"></div><div class="lbl"></div></div>
                    </div>
                    <div class="chart"></div>
                </div>
            </div>

            {{-- Phone: storefront --}}
            <div class="phone">
                <div class="screen">
                    <div class="notch"></div>
                    <div class="title">Relic</div>
                    <div class="item"><span class="name"></span><span class="price">€32</span></div>
                    <div class="item"><span class="name s"></span><span class="price">€18</span></div>
                    <div class="item"><span class="name"></span><span class="price">€74</span></div>
                    <div class="item"><span class="name s"></span><span class="price">€49</span></div>
                    <div class="item"><span class="name"></span><span class="price">€127</span></div>
                </div>
            </div>

            {{-- Floating mini metric cards --}}
            <div class="mini-card m1">
                <div class="label">Active stores</div>
                <div class="value"><span class="cyan" data-counter="247">0</span></div>
            </div>
            <div class="mini-card m2">
                <div class="label">Last 24h</div>
                <div class="value"><span class="green">+€<span data-counter="12480">0</span></span></div>
            </div>
        </div>
    </section>

    {{-- Live activity ticker — scrolls horizontally on loop --}}
    <div class="ticker-strip" aria-hidden="true">
        <div class="ticker">
            @php
                $samples = [
                    ['store' => 'aurora.bg',  'product' => 'Linen tote',     'amount' => '€48'],
                    ['store' => 'acme.bg',    'product' => 'Starter widget', 'amount' => '€19'],
                    ['store' => 'relic.bg',   'product' => 'Brass opener',   'amount' => '€32'],
                    ['store' => 'nova.bg',    'product' => 'Field notebook', 'amount' => '€14'],
                    ['store' => 'oslo.bg',    'product' => 'Espresso tin',   'amount' => '€22'],
                    ['store' => 'kyoto.bg',   'product' => 'Premium bundle', 'amount' => '€127'],
                    ['store' => 'meridian.bg','product' => 'Coffee blend',   'amount' => '€18'],
                    ['store' => 'pebble.bg',  'product' => 'Leather wallet', 'amount' => '€74'],
                ];
                // Repeat twice — animation runs from 0 to -50% to loop seamlessly.
                $rows = array_merge($samples, $samples);
            @endphp
            @foreach ($rows as $r)
                <span class="item">
                    <span class="pulse"></span>
                    <span class="from">{{ $r['store'] }} →</span>
                    <span>{{ $r['product'] }}</span>
                    <span class="amount">{{ $r['amount'] }}</span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- Stats row --}}
    <div class="stats">
        <div class="stat-card">
            <div class="label">Stores deployed</div>
            <div class="value" data-counter="1247">0</div>
            <div class="delta">+18 this week</div>
        </div>
        <div class="stat-card">
            <div class="label">Themes shipped</div>
            <div class="value" data-counter="5">0</div>
            <div class="delta">Default · Minimal · Gallery · Menu · Tech</div>
        </div>
        <div class="stat-card">
            <div class="label">Currencies supported</div>
            <div class="value" data-counter="6">0</div>
            <div class="delta">EUR · USD · GBP · CAD · AUD · BGN</div>
        </div>
        <div class="stat-card">
            <div class="label">Time to launch</div>
            <div class="value">< 5<span style="font-size: .55em; opacity: .7;">min</span></div>
            <div class="delta">From signup to live storefront</div>
        </div>
    </div>

    <footer class="foot">
        © {{ date('Y') }} GANVO
        <span class="sep">·</span>
        <a href="/preview/coming-soon-v1">← classic version</a>
    </footer>

    <script>
        // Cursor-following spotlight on the bg layer
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

        // Animated counters — scroll into view once, then count up
        const counters = document.querySelectorAll('[data-counter]');
        const ease = (t) => 1 - Math.pow(1 - t, 3);
        function animateCount(el) {
            const target = parseInt(el.dataset.counter, 10);
            const duration = 1400;
            const start = performance.now();
            function step(now) {
                const t = Math.min(1, (now - start) / duration);
                el.textContent = Math.round(target * ease(t)).toLocaleString();
                if (t < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { animateCount(e.target); io.unobserve(e.target); } });
        }, { threshold: .3 });
        counters.forEach(c => io.observe(c));

        // Notify form — same progressive enhancement as v1
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
