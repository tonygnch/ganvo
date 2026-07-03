<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Ember hard-codes its typography: Spectral (warm editorial serif display) +
         Space Mono (uppercase mono labels / tickers) + Hanken Grotesk (body).
         DM Serif Display is loaded as the alternate display the tweaker offers.
         A tactile, earthy specialty-coffee pairing. The merchant's font_family
         setting is intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,500;0,600;0,700;1,500&family=DM+Serif+Display:ital@0;1&family=Space+Mono:wght@400;700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is a roasted-terracotta. */
            --accent: {{ $store->primary_color ?: '#b0542a' }};
            --display: "Spectral", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --mono: "Space Mono", monospace;
            --bg: #f2eadc;
            --ink: #2c1e15;
            --soft: #e5d8c4;
            --soft2: #dccbb1;
            --card: #fdf8ef;
            --line: #cdb999;
            --muted: #8a7257;
            --deep: #22150d;
            /* faint hairline rule used inside cards/boards */
            --rule: #2c1e1522;
            /* ember glow used on the dark slabs */
            --glow: #3a2415;
            /* paper grain — tiny inline SVG turbulence tile, multiplied over
               the whole canvas at very low opacity so the cream reads as
               uncoated card stock rather than a flat hex fill. */
            --grain: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)' opacity='.55'/%3E%3C/svg%3E");
            /* stamped-ink mask — thresholded turbulence alpha knocks tiny
               flecks out of labels so they look overprinted by hand. */
            --stamp: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='s'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.55' numOctaves='3' stitchTiles='stitch'/%3E%3CfeComponentTransfer%3E%3CfeFuncA type='linear' slope='8' intercept='-1.1'/%3E%3C/feComponentTransfer%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23s)'/%3E%3C/svg%3E");

            --header-height: 72px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Ember palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--card));
            --primary-strong: color-mix(in srgb, var(--accent) 82%, #000);
            --secondary: var(--ink);
            --surface: var(--card);
            --border: var(--line);
            --text: var(--ink);
            --text-muted: var(--muted);
            --text-soft: var(--muted);

            /* Variant picker: squared chip, accent outline / ink fill when on. */
            --vp-radius: 2px;
            --vp-fill: var(--ink);
            --vp-on-accent: #fdf8ef;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            /* layered warm wash: brighter at the lede, toasted at the edges —
               depth instead of a flat solid. The grain sits on ::after. */
            background:
                radial-gradient(120% 80% at 50% 0%, #f7f0e3 0%, rgba(247, 240, 227, 0) 55%),
                radial-gradient(140% 100% at 50% 115%, #e9dcc5 0%, rgba(233, 220, 197, 0) 60%),
                var(--bg);
            color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden;
        }
        /* fixed paper-grain film over the whole page (incl. the dark slabs) */
        body::after { content: ""; position: fixed; inset: 0; z-index: 2000; pointer-events: none; background-image: var(--grain); background-size: 160px 160px; opacity: .16; mix-blend-mode: multiply; }
        ::selection { background: var(--accent); color: #fdf8ef; }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 36px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* placeholder fills (used wherever a real image is missing).
           Ember has no product photography, so a placeholder is a warm
           roasted-bean radial gradient. `.bloomph` is a legacy alias kept
           working for partials/templates that still reference it — here it
           just nudges to a flatter, softer earthen block. */
        .ph { position: relative; background: radial-gradient(120% 120% at 50% 25%, #6b3a22, #2c1a10); overflow: hidden; }
        .bloomph { background: radial-gradient(120% 120% at 50% 25%, #8a5232, #3a2415); }
        .ph img, .bloomph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .kicker { font-family: var(--mono); font-size: 12px; letter-spacing: .16em; text-transform: uppercase; font-weight: 400; color: var(--accent); }

        /* buttons — squared, ink fill by default, accent + ghost variants */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-family: var(--mono); font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 15px 30px; border: 1.5px solid var(--ink); background: var(--ink); color: var(--bg); border-radius: 2px; transition: background-color .22s ease, color .22s ease, border-color .22s ease; }
        .btn:hover { background: transparent; color: var(--ink); }
        .btn.accent { background: var(--accent); border-color: var(--accent); color: #fdf8ef; }
        .btn.accent:hover { background: transparent; color: var(--accent); }
        .btn.outline, .btn.ghost { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover, .btn.ghost:hover { background: var(--ink); color: var(--bg); }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .55; cursor: not-allowed; background: var(--ink); color: var(--bg); }
        .btn:not(:disabled):active { transform: translateY(1px); }

        /* roast-level pip indicator — Ember signature dot row */
        .roast { display: inline-flex; gap: 4px; align-items: center; }
        .roast i { width: 8px; height: 8px; border-radius: 50%; background: var(--soft2); border: 1px solid var(--line); transition: transform .25s cubic-bezier(.19, .7, .16, 1), background-color .25s ease; }
        .roast i.on { background: var(--accent); border-color: var(--accent); }

        /* reveal on scroll */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .9s ease, transform 1s cubic-bezier(.19, .7, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .1s; } .reveal.s2 { transition-delay: .2s; } .reveal.s3 { transition-delay: .3s; } .reveal.s4 { transition-delay: .42s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } .floaty, .tick .track { animation: none !important; } .btn:not(:disabled):active, .roast i { transform: none; transition: none; } }

        /* Legacy hook: templates still render a `<div class="tape">` washi strip.
           Ember has no tape — collapse it so the markup is harmless. */
        .tape { display: none; }

        /* ticker — the announcement bar, dark mono scrolling strip */
        .tick { background: var(--deep); color: var(--soft); overflow: hidden; white-space: nowrap; }
        .tick .track { display: inline-flex; gap: 30px; padding: 9px 0; animation: tick var(--tick-dur, 30s) linear infinite; font-family: var(--mono); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--accent); }
        .tick.link a { color: inherit; }
        .tick:hover .track, .tick:focus-within .track { animation-play-state: paused; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header / nav — mockup structure: 3-column grid with the wordmark
           CENTERED, split nav (browse links left, utilities right). This is the
           Ember signature header — deliberately not a wordmark-left bar. */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(242, 234, 220, .9); backdrop-filter: blur(8px); border-bottom: 1.5px solid var(--ink); }
        .nav { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; height: 72px; }
        .nav .left, .nav .right { display: flex; gap: 22px; align-items: center; font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; min-width: 0; }
        .nav .left { justify-content: flex-start; }
        .nav .right { justify-content: flex-end; }
        .nav .left a:hover, .nav .right a:hover { color: var(--accent); }
        /* nav links draw an accent baseline in from the left on hover */
        .nav .left > a, .nav .right > a { position: relative; padding-bottom: 3px; background-image: linear-gradient(var(--accent), var(--accent)); background-repeat: no-repeat; background-position: 0 100%; background-size: 0% 1.5px; transition: color .2s ease, background-size .3s cubic-bezier(.19, .7, .16, 1); }
        .nav .left > a:hover, .nav .right > a:hover, .nav .left > a:focus-visible, .nav .right > a:focus-visible { background-size: 100% 1.5px; }
        @media (prefers-reduced-motion: reduce) { .nav .left > a, .nav .right > a { transition: color .2s ease; } }
        .logo { font-family: var(--display); font-weight: 700; font-size: 26px; letter-spacing: -.01em; color: var(--ink); white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 9px; }
        .logo::before { content: ""; width: 10px; height: 10px; border-radius: 50%; background: radial-gradient(circle at 34% 30%, #d9834f, var(--accent) 60%, color-mix(in srgb, var(--accent) 65%, #000)); flex-shrink: 0; }
        .logo img { height: 30px; width: auto; display: block; }
        .logo:has(img)::before { display: none; }
        .bag .n { background: var(--accent); color: #fdf8ef; min-width: 19px; height: 19px; padding: 0 5px; border-radius: 2px; font-size: 11px; display: inline-grid; place-items: center; margin-left: 5px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--ink); }

        /* dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); font-family: var(--mono); font-size: 12px; text-transform: uppercase; user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--accent); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); right: 0; min-width: 200px; background: var(--card); border: 1.5px solid var(--ink); border-radius: 3px; padding: 8px; z-index: 70; box-shadow: 6px 6px 0 var(--soft2); }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 2px; font-size: 14px; color: var(--ink); text-transform: none; font-family: var(--body); }
        .menu-items a:hover { background: var(--bg); }
        .menu-items a.active { color: var(--accent); font-weight: 600; }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 220px; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent); font-weight: 600; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 70; background: var(--bg); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 32px; text-align: center; opacity: 0; visibility: hidden; transition: opacity .4s ease, visibility .4s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 22px; right: 28px; background: none; border: none; font-size: 26px; color: var(--ink); }
        .m-drawer .mlogo { position: absolute; top: 24px; left: 32px; font-family: var(--display); font-weight: 700; font-size: 22px; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 4px; }
        .m-drawer nav a { font-family: var(--display); font-weight: 600; font-size: 38px; padding: 6px 0; }
        .m-drawer nav a em { font-style: italic; color: var(--accent); }
        .m-drawer .mfoot { position: absolute; bottom: 34px; font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }

        /* section + page heads (shared by inner pages) */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; text-align: left; border-bottom: 2px solid var(--ink); padding-bottom: 14px; margin: 80px 0 28px; }
        .sec-head .kicker { display: block; }
        .sec-head h2 { font-family: var(--display); font-weight: 700; font-size: clamp(28px, 3.6vw, 44px); line-height: 1.04; letter-spacing: -.015em; }
        .sec-head h2 em { font-style: italic; color: var(--accent); }
        .sec-head .more { display: inline-block; font-family: var(--mono); font-size: 12px; text-transform: uppercase; color: var(--accent); border-bottom: 1px solid currentColor; padding-bottom: 1px; }
        .page-head { text-align: left; padding: 48px 0 26px; border-bottom: 2px solid var(--ink); }
        .page-head .crumb { font-family: var(--mono); font-size: 12px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--display); font-weight: 700; font-size: clamp(38px, 5vw, 64px); margin-top: 8px; line-height: 1.02; letter-spacing: -.018em; }
        .page-head h1 em { font-style: italic; color: var(--accent); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 8px 0 0; }

        /* product card — bordered "blend" tile (home, catalog, collection, related) */
        .blooms { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; padding-top: 14px; }
        .bcard { display: flex; flex-direction: column; color: inherit; background: var(--card); border: 1.5px solid var(--ink); border-radius: 4px; overflow: hidden; cursor: pointer; position: relative; transition: transform .22s ease, box-shadow .22s ease; }
        .bcard:hover { transform: translateY(-5px); box-shadow: 6px 6px 0 var(--soft2), 0 22px 40px -28px rgba(44, 30, 21, .5); }
        .bcard .pic { height: 230px; position: relative; overflow: hidden; }
        .bcard .pic img { transition: transform .6s cubic-bezier(.19, .7, .16, 1); }
        .bcard:hover .pic img { transform: scale(1.045); }
        /* badge — hand-stamped chip: slight tilt + overprint mask */
        .bcard .badge { position: absolute; top: 12px; left: 12px; background: var(--card); border: 1px solid var(--ink); font-family: var(--mono); font-size: 10px; text-transform: uppercase; letter-spacing: .04em; padding: 4px 10px; border-radius: 2px; color: var(--accent); z-index: 2; transform: rotate(-2deg); -webkit-mask-image: var(--stamp); mask-image: var(--stamp); }
        .bcard .body { padding: 20px 22px 22px; }
        .bcard .cat { font-family: var(--mono); font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
        .bcard h3 { font-family: var(--display); font-weight: 600; font-size: 23px; margin: 4px 0 12px; line-height: 1.12; letter-spacing: -.01em; }
        .bcard .meta { display: flex; align-items: center; justify-content: space-between; gap: 10px; border-top: 1px solid var(--rule); padding-top: 14px; }
        .bcard .meta:not(:has(.roast)) { justify-content: flex-end; }
        .bcard .pr { font-family: var(--display); font-weight: 600; font-size: 20px; font-variant-numeric: tabular-nums; color: var(--accent); transition: color .2s ease; }
        .bcard:hover .pr { color: var(--primary-strong); }
        .bcard:hover .roast i.on { transform: scale(1.25); }
        @media (prefers-reduced-motion: reduce) { .bcard, .bcard:hover { transform: none; } .bcard .pic img, .bcard:hover .pic img { transform: none; transition: none; } .bcard:hover .roast i.on { transform: none; } }

        /* footer — dark roastery slab with a faint ember glow rising from
           the roaster below the fold */
        footer.site { background: radial-gradient(70% 90% at 50% 118%, #4d2a12 0%, rgba(77, 42, 18, 0) 62%), var(--deep); color: var(--soft); padding: 60px 0 32px; margin-top: 60px; border-top: 1.5px solid var(--ink); position: relative; }
        footer.site::before { content: ""; position: absolute; top: 6px; left: 0; right: 0; height: 1px; background: #4a382a; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-size: 24px; color: var(--soft); }
        .fgrid .logo::before { background: var(--accent); }
        .fcol h4 { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: #a0876c; margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: #cdbdab; }
        .fcol a:hover { color: #e0a07a; }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid #4a382a; font-family: var(--mono); font-size: 12px; color: #a0876c; gap: 16px; flex-wrap: wrap; }
        .fbot a { color: #e0a07a; }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--card); color: var(--ink); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1.5px solid var(--ink); border-radius: 3px; box-shadow: 6px 6px 0 var(--soft2); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "✦"; color: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .blooms { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav { grid-template-columns: auto 1fr auto; }
            .nav .left { display: none; }
            .menu-toggle { display: block; }
            .logo { justify-content: flex-start; }
            .nav .right { gap: 16px; }
            .blooms { grid-template-columns: 1fr; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .nav .right .lbl { display: none; }
        }
    </style>
</head>
<body>
    @php
        $csAnnouncement = $store->announcementBar();
        $csNavMenu = $store->navMenuItems();
        $customer = auth('customer')->user();
        $currentLocale = app()->getLocale();
        $languages = \App\Http\Middleware\SetLocale::available();
        $supportedCurrencies = $store->supportedDisplayCurrencies();
        $cartCount = \App\Services\Cart::forCurrent()->itemCount();
        $logoUrl = $store->logo_path
            ? \Illuminate\Support\Facades\Storage::url($store->logo_path)
            : null;
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        @php
            $tape = trim($csAnnouncement['text']);
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">✦</span>&nbsp; ';
            $tapeHalf = str_repeat($tapeUnit, 4);
            $isStatic = (int) $csAnnouncement['speed_px'] === 0;
        @endphp
        <div class="tick {{ $csAnnouncement['link'] ? 'link' : '' }}" data-tick data-pps="{{ (int) $csAnnouncement['speed_px'] }}"
             @if ($isStatic) data-static="1" @endif aria-label="{{ $tape }}">
            <div class="track" aria-hidden="true">
                @if ($csAnnouncement['link'])
                    <a class="tick-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1">{!! $tapeHalf !!}</a><a class="tick-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1">{!! $tapeHalf !!}</a>
                @else
                    <span class="tick-half">{!! $tapeHalf !!}</span><span class="tick-half">{!! $tapeHalf !!}</span>
                @endif
            </div>
            @if ($csAnnouncement['link'])<a href="{{ $csAnnouncement['link'] }}" class="sr-only">{{ $tape }}</a>@endif
        </div>
    @endif

    <header class="site">
        <div class="wrap">
            <div class="nav">
                <button class="menu-toggle" aria-label="Menu">☰</button>
                <div class="left">
                    @if (! empty($csNavMenu))
                        @foreach ($csNavMenu as $item)
                            @if (! empty($item['children']))
                                <details class="menu nav-menu">
                                    <summary><span>{{ $item['label'] }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                                    <div class="menu-items" role="menu">
                                        @if ($item['url'])<a role="menuitem" href="{{ $item['url'] }}" class="view-all">{{ __('site.storefront.featured.browse_all') }}</a>@endif
                                        @foreach ($item['children'] as $child)
                                            @php $depth = (int) ($child['depth'] ?? 0); @endphp
                                            <a role="menuitem" href="{{ $child['url'] }}" data-depth="{{ $depth }}" @if ($depth > 0) style="--d: {{ $depth }};" @endif>{{ $child['label'] }}</a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @else
                        <a href="/">{{ __('site.storefront.nav.shop') }}</a>
                        <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
                    @endif
                </div>
                <a class="logo" href="/">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{{ $tenant->name }}@endif
                </a>
                <div class="right">
                    <details class="menu">
                        <summary aria-label="{{ __('site.lang.switch') }}"><span>{{ strtoupper($currentLocale) }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                        <div class="menu-items" role="menu">
                            @foreach ($languages as $code => $name)
                                <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif"><span>{{ $name }}</span><svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg></a>
                            @endforeach
                        </div>
                    </details>
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu">
                            <summary aria-label="{{ __('site.currency.switch') }}"><span>{{ $displayCurrency }}</span><svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items" role="menu">
                                @foreach ($supportedCurrencies as $code)
                                    <a role="menuitem" href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif"><span>{{ \App\Services\Money::symbol($code) }} · {{ $code }}</span><svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg></a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        <a href="{{ $customer ? '/account' : '/account/login' }}"><span class="lbl">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</span></a>
                    @endif
                    <a class="bag" href="/cart">{{ __('site.common.cart') }}<span class="n">{{ $cartCount }}</span></a>
                </div>
            </div>
        </div>
    </header>

    <div class="m-drawer" id="mDrawer">
        <div class="mlogo">{{ $tenant->name }}</div>
        <button class="mclose" id="mClose" aria-label="Close menu">✕</button>
        <nav>
            @if (! empty($csNavMenu))
                @foreach ($csNavMenu as $item)
                    <a href="{{ $item['url'] ?: '/' }}">{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="/">{{ __('site.storefront.nav.shop') }}</a>
                <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
            @endif
            @if ($store->showsAccountUi())
                <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
            @endif
            <a href="/cart">{{ __('site.common.cart') }}</a>
        </nav>
        <div class="mfoot">{{ __('site.storefront.footer.tagline') }}</div>
    </div>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="logo">{{ $tenant->name }}</div>
                    <p style="color: var(--muted); max-width: 30ch; margin-top: 14px; font-size: 14px;">{{ __('site.storefront.footer.tagline') }}</p>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_shop') }}</h4>
                    <a href="/">{{ __('site.storefront.footer.all_products') }}</a>
                    <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
                    <a href="/cart">{{ __('site.common.cart') }}</a>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.storefront.footer.col_help') }}</h4>
                    <a href="#">{{ __('site.storefront.footer.shipping') }}</a>
                    <a href="#">{{ __('site.storefront.footer.returns') }}</a>
                    <a href="#">{{ __('site.storefront.footer.contact') }}</a>
                </div>
                <div class="fcol">
                    <h4>{{ __('site.lang.switch') }}</h4>
                    @foreach ($languages as $code => $name)
                        <a href="/lang/{{ $code }}">{{ $name }}</a>
                    @endforeach
                </div>
            </div>
            <div class="fbot">
                <span>© {{ date('Y') }} {{ $tenant->name }} — {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        // Ticker — set duration from the merchant's px/sec rate so perceived
        // speed is length-independent. The track holds two identical halves and
        // translates -50%; duration = halfWidth / pps. data-pps="0" = static.
        (function () {
            var bar = document.querySelector('[data-tick]');
            if (! bar) return;
            var pps = parseInt(bar.getAttribute('data-pps'), 10) || 0;
            if (pps <= 0) return;
            var half = bar.querySelector('.tick-half');
            if (! half) return;
            function apply() {
                var w = half.getBoundingClientRect().width;
                if (! w) return;
                var dur = Math.max(8, Math.min(180, w / pps));
                bar.style.setProperty('--tick-dur', dur.toFixed(2) + 's');
            }
            apply();
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(apply).catch(function () {});
            var t; window.addEventListener('resize', function () { clearTimeout(t); t = setTimeout(apply, 150); }, { passive: true });
        })();

        // Mobile drawer.
        (function () {
            var drawer = document.getElementById('mDrawer');
            var toggle = document.querySelector('.menu-toggle');
            var close = document.getElementById('mClose');
            if (! drawer || ! toggle) return;
            function open() { drawer.classList.add('open'); document.body.style.overflow = 'hidden'; }
            function shut() { drawer.classList.remove('open'); document.body.style.overflow = ''; }
            toggle.addEventListener('click', function (e) { e.stopPropagation(); open(); });
            if (close) close.addEventListener('click', shut);
            drawer.querySelectorAll('nav a').forEach(function (a) { a.addEventListener('click', shut); });
        })();

        // Reveal on scroll.
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
            document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
        })();
    </script>
    @stack('scripts')
</body>
</html>
