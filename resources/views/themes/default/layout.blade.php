<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Atelier hard-codes its typography — the design depends on its
         Archivo Expanded display + Cormorant Garamond serif + Hanken
         Grotesk body. The merchant's font_family setting is intentionally
         ignored for this theme. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Archivo+Expanded:wght@600;700;800&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Everything else is the Atelier palette. */
            --accent: {{ $store->primary_color ?: '#b23a2e' }};
            --display: "Archivo Expanded", sans-serif;
            --serif: "Cormorant Garamond", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --ink: #100f0d;
            --paper: #ece7dd;
            --soft: #ddd6c8;
            --soft2: #cfc7b6;
            --muted: #867f72;
            --rule: #100f0d22;
            --line: #d3ccbe;

            /* Aliases — shared storefront pages (cart, checkout, order, auth)
               reference these legacy default-theme tokens. Mapping them here
               keeps those pages rendering coherently with Atelier without a
               per-page rewrite. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 12%, var(--paper));
            --primary-strong: var(--accent);
            --secondary: var(--ink);
            --bg: var(--paper);
            --surface: #ffffff;
            --border: var(--line);
            --text: var(--ink);
            --text-muted: #4a4338;
            --text-soft: var(--muted);

            /* Variant picker: sharp, ink-filled selected chip (Atelier). */
            --vp-radius: 2px;
            --vp-fill: var(--accent);
            --vp-on-accent: var(--paper);

            /* Premium easing curves — ease-out for entering UI, a softer
               curve for fluid hover/scroll polish. Used everywhere instead
               of linear so motion reads couture rather than mechanical. */
            --ease-out: cubic-bezier(.19, .7, .16, 1);
            --ease-soft: cubic-bezier(.4, 0, .2, 1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            background: var(--paper);
            color: var(--ink);
            font-family: var(--body);
            line-height: 1.5;
            font-size: 16px;
            min-height: 100vh;
            overflow-x: hidden;
        }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1320px; margin: 0 auto; padding: 0 36px; }

        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* placeholder (used by partials where image is missing) */
        .ph {
            position: relative;
            background: var(--soft);
            background-image: repeating-linear-gradient(135deg, rgba(16,15,13,.05) 0 11px, transparent 11px 22px);
            display: grid;
            place-items: center;
            overflow: hidden;
        }
        .ph span {
            font-family: var(--body);
            font-size: 10px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            background: rgba(236,231,221,.66);
            padding: 5px 10px;
        }
        .ph.dark { background: #1c1a17; background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.04) 0 11px, transparent 11px 22px); }
        .ph.dark span { background: rgba(16,15,13,.5); color: #b9b1a2; }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        .disp { font-family: var(--display); text-transform: uppercase; letter-spacing: -.02em; line-height: .92; }
        .kicker { font-family: var(--body); font-size: 11px; letter-spacing: .28em; text-transform: uppercase; font-weight: 600; }
        .ital { font-family: var(--serif); font-style: italic; text-transform: none; letter-spacing: 0; }
        .serif { font-family: var(--serif); }

        /* buttons */
        .btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 11px;
            letter-spacing: .2em;
            text-transform: uppercase;
            font-weight: 600;
            padding: 17px 34px;
            border: 1px solid currentColor;
            background: var(--ink);
            color: var(--paper);
            transition: background-color .35s var(--ease-soft), color .35s var(--ease-soft),
                        transform .35s var(--ease-out), box-shadow .35s var(--ease-out);
            will-change: transform;
        }
        .btn:hover { background: transparent; color: var(--ink); transform: translateY(-2px); }
        .btn:active { transform: translateY(0); }
        .btn.ghost { background: transparent; color: inherit; }
        .btn.ghost:hover { background: var(--ink); color: var(--paper); transform: translateY(-2px); }
        .btn.red { background: var(--accent); border-color: var(--accent); color: #fff; }
        .btn.red:hover {
            background: var(--accent);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -12px color-mix(in srgb, var(--accent) 75%, transparent);
        }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover { background: var(--ink); color: var(--paper); transform: translateY(-2px); }
        .btn.block { width: 100%; }
        .btn .arc { transition: transform .35s var(--ease-out); }
        .btn:hover .arc { transform: translateX(5px); }
        @media (prefers-reduced-motion: reduce) {
            .btn, .btn:hover, .btn:active, .btn.ghost:hover, .btn.red:hover, .btn.outline:hover { transform: none; }
        }

        /* scroll progress */
        .scrollbar { position: fixed; top: 0; left: 0; height: 3px; width: 100%; background: var(--accent); transform: scaleX(0); transform-origin: 0 50%; z-index: 100; will-change: transform; }

        /* reveal */
        .rv { opacity: 0; transform: translateY(30px); }
        .rv.rv-in {
            opacity: 1;
            transform: none;
            transition: opacity .9s var(--ease-soft), transform 1s var(--ease-out);
        }
        @media (prefers-reduced-motion: reduce) {
            .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; }
            .tick .track, .brandmarq .track { animation: none !important; }
        }

        /* ticker */
        .tick { background: var(--ink); color: var(--paper); overflow: hidden; white-space: nowrap; }
        .tick .track { display: inline-flex; gap: 38px; padding: 9px 0; animation: tick 26s linear infinite; font-size: 11px; letter-spacing: .24em; text-transform: uppercase; }
        .tick .track span { display: inline-flex; gap: 38px; }
        .tick .track .dot { color: var(--accent); }
        @keyframes tick { to { transform: translateX(-50%); } }

        /* announcement marquee (merchant-controlled strip) */
        .marquee {
            background: var(--ink);
            color: var(--paper);
            text-align: center;
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            padding: 9px;
        }
        .marquee a { color: inherit; border-bottom: 1px solid rgba(236,231,221,.4); padding-bottom: 1px; }

        /* nav */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(236,231,221,.9); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid var(--ink); }
        .nav { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; height: 64px; }
        .nav .left, .nav .right { display: flex; gap: 26px; align-items: center; font-size: 11px; letter-spacing: .18em; text-transform: uppercase; }
        .nav .right { justify-content: flex-end; }
        .nav a.lk { position: relative; padding: 4px 0; color: var(--ink); transition: color .3s var(--ease-soft); }
        .nav a.lk:hover { color: var(--accent); }
        .nav a.lk::after { content: ""; position: absolute; left: 0; bottom: 0; height: 1px; width: 0; background: var(--accent); transition: width .4s var(--ease-out); }
        .nav a.lk:hover::after { width: 100%; }
        .logo { font-family: var(--display); font-weight: 800; font-size: 22px; letter-spacing: .16em; text-transform: uppercase; text-align: center; color: var(--ink); white-space: nowrap; }
        .logo img { height: 30px; width: auto; display: inline-block; }
        .bag { display: inline-flex; align-items: center; gap: 7px; }
        .bag .n {
            background: var(--accent);
            color: #fff;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9px;
            font-size: 10px;
            display: inline-grid;
            place-items: center;
            font-family: var(--body);
        }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; z-index: 80; position: relative; color: var(--ink); }

        /* language / currency / nav dropdowns */
        .menu { position: relative; }
        .menu summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--ink);
            font-size: 11px;
            letter-spacing: .18em;
            text-transform: uppercase;
            user-select: none;
            padding: 4px 0;
            position: relative;
        }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary::after { content: ""; position: absolute; left: 0; bottom: 0; height: 1px; width: 0; background: var(--accent); transition: width .3s; }
        .menu:hover summary::after, .menu[open] summary::after { width: 100%; }
        .menu .chev { width: 10px; height: 10px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            min-width: 190px;
            background: rgba(236,231,221,.92);
            backdrop-filter: blur(12px) saturate(1.2);
            -webkit-backdrop-filter: blur(12px) saturate(1.2);
            border: 1px solid var(--ink);
            padding: 6px;
            z-index: 70;
            box-shadow: 0 24px 48px -18px rgba(16,15,13,.35);
            transform-origin: top;
            animation: menuPop .28s var(--ease-out);
        }
        @keyframes menuPop { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: reduce) { .menu-items { animation: none; } }
        .menu-items a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            color: var(--ink);
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            transition: background-color .2s var(--ease-soft), color .2s var(--ease-soft);
        }
        .menu-items a:hover { background: var(--soft); }
        .menu-items a.active { color: var(--accent); }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: var(--accent); stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }

        /* nav dropdown flyout (category/collection children) */
        .menu.nav-menu .menu-items { right: auto; left: 0; min-width: 230px; }
        .menu.nav-menu .menu-items a { justify-content: flex-start; gap: 8px; }
        .menu.nav-menu .menu-items a.view-all { color: var(--ink); font-weight: 700; border-bottom: 1px solid var(--line); margin-bottom: 4px; padding-bottom: 12px; }
        .menu.nav-menu .menu-items a.view-all:hover { color: var(--accent); }
        .menu.nav-menu .menu-items a[data-depth] { padding-left: calc(12px + 18px * var(--d, 0)); }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"])::before { content: "└"; display: inline-block; margin-right: 4px; color: var(--muted); font-weight: 400; }

        /* mobile drawer */
        .m-drawer {
            position: fixed; inset: 0; z-index: 75; background: var(--ink); color: var(--paper);
            display: flex; flex-direction: column; justify-content: center; padding: 0 32px;
            opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility .45s;
        }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 18px; right: 26px; background: none; border: none; color: var(--paper); font-size: 26px; cursor: pointer; }
        .m-drawer .mtop { position: absolute; top: 20px; left: 32px; font-family: var(--display); font-weight: 800; text-transform: uppercase; letter-spacing: .16em; font-size: 18px; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 6px; }
        .m-drawer nav a {
            font-family: var(--display); font-weight: 800; text-transform: uppercase;
            font-size: clamp(30px, 11vw, 54px); line-height: 1.1; letter-spacing: -.02em;
            opacity: 0; transform: translateY(24px); transition: opacity .5s ease, transform .6s cubic-bezier(.19,.7,.16,1);
        }
        .m-drawer.open nav a { opacity: 1; transform: none; }
        .m-drawer nav a:nth-child(1) { transition-delay: .08s; }
        .m-drawer nav a:nth-child(2) { transition-delay: .14s; }
        .m-drawer nav a:nth-child(3) { transition-delay: .20s; }
        .m-drawer nav a:nth-child(4) { transition-delay: .26s; }
        .m-drawer nav a:nth-child(5) { transition-delay: .32s; }
        .m-drawer nav a:nth-child(6) { transition-delay: .38s; }
        .m-drawer nav a .ix { font-family: var(--body); font-size: 11px; letter-spacing: .2em; color: var(--accent); vertical-align: super; margin-right: 10px; }
        .m-drawer .mfoot { position: absolute; bottom: 30px; left: 32px; right: 32px; display: flex; justify-content: space-between; font-size: 11px; letter-spacing: .18em; text-transform: uppercase; opacity: 0; transition: opacity .5s ease .4s; }
        .m-drawer.open .mfoot { opacity: .7; }
        .m-drawer .mfoot a { cursor: pointer; }
        @media (prefers-reduced-motion: reduce) { .m-drawer, .m-drawer nav a, .m-drawer .mfoot { transition: none !important; } }

        /* toast */
        .toast {
            position: fixed; top: 24px; right: 24px; background: var(--ink); color: var(--paper);
            padding: 14px 18px; z-index: 100; font-size: 12px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase;
            display: flex; align-items: center; gap: 10px; box-shadow: 0 20px 40px -10px rgba(16,15,13,.4);
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        .toast::before { content: ""; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-1rem); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-1rem); } }

        /* section heading shared by inner pages */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; margin: 84px 0 28px; border-bottom: 1px solid var(--ink); padding-bottom: 16px; gap: 16px; flex-wrap: wrap; }
        .sec-head h2 { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: clamp(24px, 3vw, 40px); letter-spacing: -.01em; }
        .sec-head a { font-size: 11px; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .sec-head a:hover { color: var(--ink); }

        /* product grid + card (shared by index / catalog / collection / related) */
        .pgrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
        .pcard { cursor: pointer; position: relative; display: block; color: inherit; transition: transform .5s var(--ease-out); will-change: transform; }
        .pcard:hover { transform: translateY(-6px); }
        .pcard .imgwrap { position: relative; overflow: hidden; height: 380px; margin-bottom: 13px; background: var(--soft); }
        .pcard .imgwrap .img { position: absolute; inset: 0; transition: transform 1s var(--ease-out); }
        .pcard:hover .imgwrap .img { transform: scale(1.07); }
        /* Sheen — a soft light sweep across the image on hover. Pure
           transform/opacity, gated by reduced-motion below. */
        .pcard .imgwrap::after {
            content: "";
            position: absolute;
            top: 0; bottom: 0;
            left: -60%;
            width: 50%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.28), transparent);
            transform: skewX(-18deg);
            opacity: 0;
            pointer-events: none;
            z-index: 1;
        }
        .pcard:hover .imgwrap::after { animation: pcardSheen .9s var(--ease-soft); }
        @keyframes pcardSheen {
            0% { left: -60%; opacity: 0; }
            18% { opacity: 1; }
            100% { left: 120%; opacity: 0; }
        }
        .pcard .over { position: absolute; left: 0; right: 0; bottom: 0; padding: 14px; background: linear-gradient(to top, rgba(16,15,13,.85), transparent); transform: translateY(101%); transition: transform .5s var(--ease-out); z-index: 2; }
        .pcard:hover .over { transform: translateY(0); }
        .pcard .over .q { display: inline-flex; gap: 8px; color: #fff; font-size: 11px; letter-spacing: .16em; text-transform: uppercase; border: 1px solid rgba(255,255,255,.5); padding: 9px 14px; transition: background-color .35s var(--ease-soft), border-color .35s var(--ease-soft); }
        .pcard:hover .over .q { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.8); }
        .pcard .tag { font-family: var(--body); font-size: 10px; letter-spacing: .16em; text-transform: uppercase; color: var(--accent); }
        .pcard .rowt { display: flex; justify-content: space-between; align-items: baseline; margin-top: 3px; gap: 12px; }
        .pcard .nm { font-family: var(--serif); font-size: 19px; transition: color .3s var(--ease-soft); }
        .pcard:hover .nm { color: var(--accent); }
        .pcard .pr { font-size: 13px; color: var(--muted); white-space: nowrap; }
        @media (prefers-reduced-motion: reduce) {
            .pcard, .pcard:hover { transform: none; }
            .pcard:hover .imgwrap::after { animation: none; }
        }

        /* inner-page editorial header */
        .ed-head { display: flex; align-items: flex-end; justify-content: space-between; border-bottom: 1px solid var(--ink); padding: 44px 0 18px; flex-wrap: wrap; gap: 12px; }
        .ed-head .crumb { font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); }
        .ed-head .crumb a:hover { color: var(--ink); }
        .ed-head h1 { font-family: var(--display); font-weight: 800; text-transform: uppercase; font-size: clamp(38px, 6vw, 84px); line-height: .9; margin-top: 10px; letter-spacing: -.02em; }
        .ed-head .meta { text-align: right; font-size: 12px; color: var(--muted); max-width: 30ch; }

        /* footer */
        footer.site { border-top: 1px solid var(--ink); margin-top: 60px; padding: 46px 0 40px; }
        .fgrid { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .wm { font-family: var(--display); font-weight: 800; font-size: 30px; letter-spacing: .14em; text-transform: uppercase; }
        .fcol h4 { font-size: 10px; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 13px; margin-bottom: 10px; color: #4a4338; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--rule); font-size: 11px; letter-spacing: .06em; color: var(--muted); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: inherit; border-bottom: 1px solid currentColor; padding-bottom: 1px; }

        @media (max-width: 1080px) {
            .pgrid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav .left { display: none; }
            .nav { grid-template-columns: auto 1fr auto; }
            .menu-toggle { display: block; }
            .pgrid { grid-template-columns: 1fr 1fr; gap: 14px; }
            .pcard .imgwrap { height: 240px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .ed-head .meta { text-align: left; }
        }
    </style>
    {!! $theme->headExtras() !!}
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

    @if ($theme->on('scroll_progress'))
        <div class="scrollbar" id="scrollbar"></div>
    @endif

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        <div class="marquee">
            @if ($csAnnouncement['link'])
                <a href="{{ $csAnnouncement['link'] }}">{{ $csAnnouncement['text'] }}</a>
            @else
                <span>{{ $csAnnouncement['text'] }}</span>
            @endif
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
                                    <summary>
                                        <span>{{ $item['label'] }}</span>
                                        <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg>
                                    </summary>
                                    <div class="menu-items" role="menu">
                                        @if ($item['url'])
                                            <a role="menuitem" href="{{ $item['url'] }}" class="view-all">
                                                <span>{{ __('site.storefront.featured.browse_all') }}</span>
                                            </a>
                                        @endif
                                        @foreach ($item['children'] as $child)
                                            @php $depth = (int) ($child['depth'] ?? 0); @endphp
                                            <a role="menuitem" href="{{ $child['url'] }}"
                                               data-depth="{{ $depth }}"
                                               @if ($depth > 0) style="--d: {{ $depth }};" @endif>
                                                <span>{{ $child['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a class="lk" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @else
                        <a class="lk" href="/">{{ __('site.storefront.nav.shop') }}</a>
                        <a class="lk" href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
                    @endif
                </div>
                <a class="logo" href="/">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">
                    @else
                        {{ $tenant->name }}
                    @endif
                </a>
                <div class="right">
                    <details class="menu">
                        <summary aria-label="{{ __('site.lang.switch') }}">
                            <span>{{ strtoupper($currentLocale) }}</span>
                            <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg>
                        </summary>
                        <div class="menu-items" role="menu">
                            @foreach ($languages as $code => $name)
                                <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">
                                    <span>{{ $name }}</span>
                                    <svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </details>
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu">
                            <summary aria-label="{{ __('site.currency.switch') }}">
                                <span>{{ $displayCurrency }}</span>
                                <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg>
                            </summary>
                            <div class="menu-items" role="menu">
                                @foreach ($supportedCurrencies as $code)
                                    <a role="menuitem" href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif">
                                        <span>{{ \App\Services\Money::symbol($code) }} · {{ $code }}</span>
                                        <svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        @if ($customer)
                            <a class="lk" href="/account">{{ __('site.common.my_account') }}</a>
                        @else
                            <a class="lk" href="/account/login">{{ __('site.common.sign_in') }}</a>
                        @endif
                    @endif
                    <a class="lk bag" href="/cart">
                        {{ __('site.common.cart') }} <span class="n">{{ $cartCount }}</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Mobile drawer mirrors the nav menu (or the default shop links). --}}
    <div class="m-drawer" id="mDrawer">
        <div class="mtop">{{ $tenant->name }}</div>
        <button class="mclose" id="mClose" aria-label="Close menu">✕</button>
        <nav>
            @if (! empty($csNavMenu))
                @foreach ($csNavMenu as $i => $item)
                    <a href="{{ $item['url'] ?: '/' }}"><span class="ix">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="/"><span class="ix">01</span>{{ __('site.storefront.nav.shop') }}</a>
                <a href="/#featured"><span class="ix">02</span>{{ __('site.storefront.nav.featured') }}</a>
            @endif
            @if ($store->showsAccountUi())
                <a href="{{ $customer ? '/account' : '/account/login' }}"><span class="ix">★</span>{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
            @endif
        </nav>
        <div class="mfoot"><a href="/cart">{{ __('site.common.cart') }} ({{ $cartCount }})</a><span>{{ $tenant->name }}</span></div>
    </div>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="wm">{{ $tenant->name }}</div>
                    <p style="color: var(--muted); max-width: 32ch; margin-top: 16px; font-size: 13px;">
                        {{ __('site.storefront.footer.tagline') }}
                    </p>
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
                <span>© {{ date('Y') }} {{ $tenant->name }}. {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        // Mobile drawer open/close.
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

        // Scroll-driven reveal (each page is a real navigation, not a SPA
        // screen swap, so we just observe .rv elements as they enter).
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.rv').forEach(function (el) { el.classList.add('rv-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e, i) {
                    if (e.isIntersecting) {
                        e.target.style.transitionDelay = Math.min(i * 55, 360) + 'ms';
                        e.target.classList.add('rv-in');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
        })();

        // Scroll progress bar.
        (function () {
            var bar = document.getElementById('scrollbar');
            if (! bar) return;
            var ticking = false;
            function onScroll() {
                var y = window.scrollY || 0;
                var docH = document.documentElement.scrollHeight - window.innerHeight;
                bar.style.transform = 'scaleX(' + (docH > 0 ? Math.min(y / docH, 1) : 0) + ')';
                ticking = false;
            }
            window.addEventListener('scroll', function () { if (! ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
            onScroll();
        })();
    </script>
</body>
</html>
