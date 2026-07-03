<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Kiln hard-codes its typography: Schibsted Grotesk (display / labels),
         Newsreader (serif accents incl. italic) + Hanken Grotesk (body).
         A calm, tactile handmade-ceramics pairing. The merchant's font_family
         setting is intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&family=Hanken+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is a muted clay. */
            --accent: {{ $store->primary_color ?: '#a9774a' }};
            --display: "Schibsted Grotesk", sans-serif;
            --serif: "Newsreader", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            /* Stone palette, cooled a step toward grey-green so Kiln reads as
               damp studio stone rather than warm cream. */
            --bg: #e9e7e0;
            --ink: #33322c;
            --soft: #ddd9cf;
            --soft2: #d1cdc0;
            --card: #f4f2eb;
            --line: #d2cec1;
            --line2: #c0bcac;
            --muted: #84816f;
            --deep: #252420;
            --stone: linear-gradient(155deg, #ccc6b8, #a8a08e);
            --stone2: linear-gradient(155deg, #c2bbaa, #9d9682);

            --header-height: 86px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Kiln palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--card));
            --primary-strong: color-mix(in srgb, var(--accent) 82%, #000);
            --secondary: var(--ink);
            --surface: var(--card);
            --border: var(--line);
            --text: var(--ink);
            --text-muted: var(--muted);
            --text-soft: var(--muted);

            /* Variant picker: square stone chip, ink fill when selected. */
            --vp-radius: 2px;
            --vp-fill: var(--ink);
            --vp-on-accent: var(--bg);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            /* layered atmosphere: a faint clay warmth top-right, a cool
               grey-green wash left — the room, not a flat wall. */
            background:
                radial-gradient(1200px 760px at 88% -12%, color-mix(in srgb, var(--accent) 7%, transparent), transparent 62%),
                radial-gradient(1000px 720px at -12% 30%, color-mix(in srgb, #6f7264 9%, transparent), transparent 58%),
                var(--bg);
            background-attachment: fixed;
            color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden;
        }
        /* glaze-fleck grain — one tiny inline-SVG noise tile over everything */
        body::after { content: ""; position: fixed; inset: 0; z-index: 90; pointer-events: none; opacity: .05; mix-blend-mode: multiply; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23g)'/%3E%3C/svg%3E"); }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        h1, h2, h3 { font-optical-sizing: auto; }
        ::selection { background: color-mix(in srgb, var(--accent) 84%, #000); color: var(--card); }
        .wrap { max-width: 1220px; margin: 0 auto; padding: 0 40px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* placeholder fills (used wherever a real image is missing) — soft
           irregular stone gradients with a faint concentric "thrown rings"
           hint, the way light falls across a pot on the wheel. */
        .ph { position: relative; background: radial-gradient(130% 100% at 72% 16%, rgba(255, 255, 255, .17), transparent 56%), var(--stone); overflow: hidden; }
        .bloomph { position: relative; overflow: hidden; background: radial-gradient(120% 100% at 28% 14%, rgba(255, 255, 255, .14), transparent 55%), var(--stone2); }
        .ph::after, .bloomph::after { content: ""; position: absolute; inset: 0; pointer-events: none; background: repeating-radial-gradient(circle at 62% 40%, color-mix(in srgb, var(--deep) 7%, transparent) 0 1px, transparent 1px 24px); }
        .ph img, .bloomph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        /* signature ornament — concentric clay rings, a pot seen from above */
        .rings-mark { width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0; background: repeating-radial-gradient(circle at 50% 46%, color-mix(in srgb, var(--accent) 80%, transparent) 0 1px, transparent 1px 8px); opacity: .55; }

        /* consistent focus treatment for form fields across every Kiln page */
        .field input:focus, .field select:focus, .field textarea:focus,
        .auth .field input:focus, .auth .field-input:focus,
        .filters input:focus, .filters select:focus,
        .summary .promo input:focus {
            border-color: var(--ink);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 16%, transparent);
        }

        .kicker { font-family: var(--display); font-size: 11px; letter-spacing: .18em; text-transform: uppercase; font-weight: 600; color: var(--accent); }

        /* buttons — squared, ink fill that inverts to outline on hover */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-family: var(--display); font-size: 12px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; padding: 15px 30px; border: 1px solid var(--ink); background: var(--ink); color: var(--bg); border-radius: 2px; transition: background-color .25s ease, color .25s ease; }
        .btn:hover { background: transparent; color: var(--ink); }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover { background: var(--ink); color: var(--bg); }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn:disabled:hover { background: var(--ink); color: var(--bg); }

        /* reveal on scroll */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity 1s ease, transform 1.1s cubic-bezier(.19, .7, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .12s; } .reveal.s2 { transition-delay: .24s; } .reveal.s3 { transition-delay: .36s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } .floaty, .tick .track { animation: none !important; } }

        /* ticker — the announcement bar, quiet scrolling strip */
        .tick { background: var(--deep); color: var(--soft); overflow: hidden; white-space: nowrap; }
        .tick .track { display: inline-flex; gap: 30px; padding: 10px 0; animation: tick var(--tick-dur, 36s) linear infinite; font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; will-change: transform; }
        .tick .track .s { color: var(--accent); }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header / nav — Kiln signature: three-column grid with the wordmark
           CENTERED, nav links splayed left, utilities right. A gallery masthead,
           not a wordmark-left storefront bar. */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(233, 231, 224, .88); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; height: 86px; }
        .logo { font-family: var(--display); font-weight: 600; font-size: 22px; letter-spacing: .3em; text-transform: uppercase; color: var(--ink); white-space: nowrap; text-align: center; padding-left: .3em; justify-self: center; }
        .logo img { height: 30px; width: auto; display: block; padding-left: 0; }
        .nav .links { display: flex; gap: 30px; font-family: var(--display); font-size: 12px; letter-spacing: .12em; text-transform: uppercase; align-items: center; justify-self: start; }
        .nav .links a:hover { color: var(--accent); }
        .nav .right { justify-self: end; display: flex; gap: 24px; align-items: center; font-family: var(--display); font-size: 12px; letter-spacing: .12em; text-transform: uppercase; }
        .nav .right a:hover { color: var(--accent); }
        .bag .n { background: var(--ink); color: var(--bg); min-width: 19px; height: 19px; padding: 0 5px; border-radius: 2px; font-size: 11px; display: inline-grid; place-items: center; margin-left: 6px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--ink); justify-self: start; }
        /* When the wordmark is centered, dropdown menus that anchor right would
           overflow; the language/currency menus live in .right so they're fine. */

        /* soft dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--accent); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); right: 0; min-width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 4px; padding: 8px; z-index: 70; box-shadow: 0 24px 50px -28px rgba(38, 36, 31, .5); }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 2px; font-size: 13px; text-transform: none; letter-spacing: 0; color: var(--ink); }
        .menu-items a:hover { background: var(--bg); }
        .menu-items a.active { color: var(--accent); font-weight: 600; }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }
        .menu.nav-menu .menu-items { left: 0; right: auto; min-width: 220px; }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: calc(12px + 14px * var(--d, 0)); color: var(--muted); }
        .menu.nav-menu .menu-items a.view-all { color: var(--accent); font-weight: 600; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 70; background: var(--bg); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 40px; text-align: center; opacity: 0; visibility: hidden; transition: opacity .45s ease, visibility .45s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 22px; right: 30px; background: none; border: none; font-size: 26px; color: var(--ink); }
        .m-drawer .mlogo { position: absolute; top: 26px; left: 40px; font-family: var(--display); font-weight: 600; font-size: 18px; letter-spacing: .28em; text-transform: uppercase; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 4px; }
        .m-drawer nav a { font-family: var(--serif); font-size: 42px; padding: 6px 0; }
        .m-drawer nav a em { font-style: italic; color: var(--accent); }
        .m-drawer .mfoot { position: absolute; bottom: 34px; font-family: var(--display); font-size: 11px; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }

        /* section head — Kiln signature: LEFT-aligned editorial header, baseline
           rule under it, with an optional "view all" link pushed to the right.
           Asymmetric, gallery-catalogue feel — not a centred title block. */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin: 90px 0 36px; border-bottom: 1px solid var(--ink); padding-bottom: 20px; }
        .sec-head .htext { display: flex; flex-direction: column; gap: 8px; }
        .sec-head .kicker { display: block; }
        .sec-head h2 { font-family: var(--serif); font-size: clamp(28px, 3.6vw, 46px); font-weight: 400; letter-spacing: -.01em; line-height: 1.02; }
        .sec-head h2 em { font-style: italic; color: var(--accent); }
        .sec-head .more { font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); white-space: nowrap; }
        .sec-head .more:hover { color: var(--accent); }
        .page-head { text-align: center; padding: 48px 0 26px; }
        .page-head .crumb { font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--serif); font-size: clamp(40px, 5vw, 64px); margin-top: 12px; font-weight: 400; letter-spacing: -.01em; }
        .page-head h1 em { font-style: italic; color: var(--accent); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 10px auto 0; }

        /* product card — calm gallery piece (used on home, catalog, collection, related) */
        .blooms { display: grid; grid-template-columns: repeat(3, 1fr); gap: 48px 30px; padding-top: 14px; }
        .bcard { display: block; color: inherit; cursor: pointer; position: relative; }
        .bcard .pic { height: 360px; margin-bottom: 16px; position: relative; overflow: hidden; transition: transform .6s cubic-bezier(.19, .7, .16, 1), box-shadow .6s ease; }
        .bcard:hover .pic { transform: translateY(-8px); box-shadow: 0 30px 46px -32px color-mix(in srgb, var(--deep) 55%, transparent); }
        .bcard .pic img { transition: transform .9s cubic-bezier(.19, .7, .16, 1); }
        .bcard:hover .pic img { transform: scale(1.015); }
        .bcard .badge { position: absolute; top: 14px; left: 14px; background: var(--card); font-family: var(--display); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; padding: 4px 10px; color: var(--accent); font-weight: 600; z-index: 2; }
        /* numbered works — quiet catalogue index in the image corner */
        .bcard .idx { position: absolute; top: 13px; right: 14px; z-index: 2; font-family: var(--display); font-size: 10px; font-weight: 600; letter-spacing: .18em; color: #fff; mix-blend-mode: difference; }
        .bcard .cat { font-family: var(--display); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        .bcard h3 { font-family: var(--serif); font-size: 22px; margin: 2px 0 4px; font-weight: 400; }
        .bcard h3::after { content: ""; display: block; width: 26px; height: 1px; background: var(--line2); margin-top: 8px; transition: width .45s cubic-bezier(.19, .7, .16, 1), background-color .45s ease; }
        .bcard:hover h3::after { width: 52px; background: var(--accent); }
        .bcard .pr { font-family: var(--display); font-weight: 600; font-size: 15px; font-variant-numeric: tabular-nums; color: var(--ink); margin-top: 6px; }
        @media (prefers-reduced-motion: reduce) { .bcard:hover .pic, .bcard:hover .pic img { transform: none; } .bcard h3::after { transition: none; } }

        /* footer */
        footer.site { background: transparent; border-top: 1px solid var(--ink); padding: 70px 0 40px; margin-top: 90px; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-size: 20px; }
        .fcol h4 { font-family: var(--display); font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); margin-bottom: 18px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 11px; color: #5d5c50; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 60px; padding-top: 24px; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--card); color: var(--ink); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1px solid var(--line); border-radius: 4px; box-shadow: 0 24px 50px -28px rgba(38, 36, 31, .5); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "❖"; color: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .blooms { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 22px; }
            /* Toggle stays in the left cell; the nav links collapse into the drawer. */
            .nav .links a, .nav .links details { display: none; }
            .menu-toggle { display: block; }
            .logo { padding-left: 0; }
            .blooms { grid-template-columns: 1fr 1fr; gap: 24px; }
            .bcard .pic { height: 240px; }
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
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">❖</span>&nbsp; ';
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
                <div class="links">
                    <button class="menu-toggle" aria-label="Menu">☰</button>
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
                    <div class="rings-mark" aria-hidden="true" style="margin-bottom: 18px;"></div>
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
