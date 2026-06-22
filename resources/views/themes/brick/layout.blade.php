<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Brick hard-codes its typography: Lexend Mega (wide brutalist display)
         + Public Sans body. The Neubrutalist Bold pairing — deliberately
         loud, geometric, nothing like the warm/editorial themes. The
         merchant's font_family setting is intentionally ignored. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Mega:wght@600;700;800;900&family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is acid lime — streetwear energy. */
            --accent: {{ $store->primary_color ?: '#d4ff00' }};
            --display: "Lexend Mega", system-ui, sans-serif;
            --body: "Public Sans", system-ui, sans-serif;
            --ink: #0a0a0a;
            --paper: #fdfbf0;
            --soft: #efe9d6;
            --soft2: #e4dcc4;
            --muted: #6b6655;
            --line: #0a0a0a;          /* brutalist borders are always black */
            --shadow: #0a0a0a;

            /* Hard offset shadow — the brutalist signature. */
            --pop: 5px 5px 0 var(--shadow);
            --pop-sm: 3px 3px 0 var(--shadow);
            --pop-lg: 8px 8px 0 var(--shadow);

            /* Legacy aliases for shared pages (cart/checkout/order/auth that
               reference default-theme tokens). Map them to the brick palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 22%, var(--paper));
            --primary-strong: var(--ink);
            --secondary: var(--ink);
            --bg: var(--paper);
            --surface: #ffffff;
            --border: var(--line);
            --text: var(--ink);
            --text-muted: #3a3730;
            --text-soft: var(--muted);

            /* Variant picker: hard-bordered chip, accent fill when selected. */
            --vp-radius: 0px;
            --vp-fill: var(--accent);
            --vp-on-accent: var(--ink);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            background: var(--paper);
            color: var(--ink);
            font-family: var(--body);
            line-height: 1.55;
            font-size: 16px;
            min-height: 100vh;
            overflow-x: hidden;
        }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1320px; margin: 0 auto; padding: 0 28px; }

        :focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }

        /* placeholder */
        .ph {
            position: relative;
            background: var(--soft);
            background-image: repeating-linear-gradient(45deg, rgba(10,10,10,.06) 0 12px, transparent 12px 24px);
            display: grid;
            place-items: center;
            overflow: hidden;
        }
        .ph span {
            font-family: var(--display);
            font-weight: 700;
            font-size: 10px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ink);
            background: var(--accent);
            border: 2px solid var(--ink);
            padding: 4px 9px;
        }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        /* buttons — hard border + offset shadow, presses down on click */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: var(--display);
            font-size: 12px;
            letter-spacing: .04em;
            text-transform: uppercase;
            font-weight: 800;
            padding: 15px 28px;
            border: 2.5px solid var(--ink);
            background: var(--paper);
            color: var(--ink);
            box-shadow: var(--pop);
            transition: transform .12s ease, box-shadow .12s ease, background-color .15s ease, color .15s ease;
        }
        .btn:hover { transform: translate(-1px, -1px); box-shadow: var(--pop-lg); }
        .btn:active { transform: translate(5px, 5px); box-shadow: 0 0 0 var(--shadow); }
        .btn.accent { background: var(--accent); color: var(--ink); }
        .btn.ink { background: var(--ink); color: var(--paper); }
        .btn.block { width: 100%; }
        .btn .arc { transition: transform .15s ease; }
        .btn:hover .arc { transform: translateX(4px); }
        @media (prefers-reduced-motion: reduce) {
            .btn, .btn:hover, .btn:active { transform: none; box-shadow: var(--pop); }
        }

        /* reveal */
        .rv { opacity: 0; transform: translateY(22px); }
        .rv.rv-in { opacity: 1; transform: none; transition: opacity .5s ease, transform .55s cubic-bezier(.2,.8,.2,1); }
        @media (prefers-reduced-motion: reduce) { .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* announcement marquee — scrolling tape, brutalist staple.
           Speed is driven by the merchant's setting as a px/sec rate: the
           track holds two identical copies of the text and translates -50%,
           so a JS pass sets animation-duration = (oneCopyWidth ÷ pps) to keep
           the *perceived* speed identical no matter how long the text is.
           --tape-dur is a sane pre-JS fallback; 'static' speed + reduced
           motion both pause it (paused, not removed, so it stays readable). */
        .marquee-tape { background: var(--ink); color: var(--accent); overflow: hidden; white-space: nowrap; border-bottom: 2.5px solid var(--ink); }
        .marquee-tape .tape { display: inline-flex; padding: 8px 0; font-family: var(--display); font-weight: 700; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; animation: tape var(--tape-dur, 24s) linear infinite; will-change: transform; }
        .marquee-tape .tape .tape-half { display: inline-block; white-space: nowrap; }
        @keyframes tape { to { transform: translateX(-50%); } }
        .marquee-tape[data-pps="0"] .tape { animation: none; }
        .marquee-tape.link a { color: inherit; }
        @media (prefers-reduced-motion: reduce) { .marquee-tape .tape { animation-play-state: paused; } }

        /* Visually-hidden but AT-reachable (for the keyboard-focusable link copy). */
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header */
        header.site { position: sticky; top: 0; z-index: 60; background: var(--paper); border-bottom: 2.5px solid var(--ink); }
        .nav { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; height: 70px; }
        .nav .left, .nav .right { display: flex; gap: 22px; align-items: center; font-family: var(--display); font-size: 12px; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
        .nav .right { justify-content: flex-end; }
        .nav a.lk { position: relative; padding: 6px 4px; }
        .nav a.lk:hover { background: var(--accent); }
        .logo { font-family: var(--display); font-weight: 900; font-size: 24px; letter-spacing: -.01em; text-transform: uppercase; text-align: center; color: var(--ink); white-space: nowrap; }
        .logo img { height: 32px; width: auto; display: inline-block; }
        .bag { display: inline-flex; align-items: center; gap: 8px; border: 2.5px solid var(--ink); padding: 8px 12px; background: var(--accent); box-shadow: var(--pop-sm); transition: transform .12s ease, box-shadow .12s ease; }
        .bag:hover { transform: translate(-1px,-1px); box-shadow: var(--pop); }
        .bag .n { font-family: var(--display); font-weight: 800; font-size: 13px; }
        .menu-toggle { display: none; background: var(--accent); border: 2.5px solid var(--ink); width: 42px; height: 42px; font-size: 18px; box-shadow: var(--pop-sm); }

        /* dropdown menus (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary {
            list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            color: var(--ink); font-family: var(--display); font-size: 12px; font-weight: 700;
            letter-spacing: .02em; text-transform: uppercase; user-select: none; padding: 6px 8px; border: 2.5px solid transparent;
        }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu[open] summary { border-color: var(--ink); background: var(--accent); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 2.5; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items {
            position: absolute; top: calc(100% + 8px); right: 0; min-width: 200px;
            background: var(--paper); border: 2.5px solid var(--ink); padding: 0; z-index: 70; box-shadow: var(--pop);
        }
        .menu-items a {
            display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 14px;
            color: var(--ink); font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
            border-bottom: 2px solid var(--ink); transition: background-color .12s ease;
        }
        .menu-items a:last-child { border-bottom: none; }
        .menu-items a:hover { background: var(--accent); }
        .menu-items a.active { background: var(--ink); color: var(--accent); }
        .menu-items .check { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
        .menu-items a:not(.active) .check { visibility: hidden; }

        .menu.nav-menu .menu-items { right: auto; left: 0; min-width: 240px; }
        .menu.nav-menu .menu-items a { justify-content: flex-start; gap: 8px; }
        .menu.nav-menu .menu-items a.view-all { background: var(--ink); color: var(--accent); }
        .menu.nav-menu .menu-items a.view-all:hover { background: var(--accent); color: var(--ink); }
        .menu.nav-menu .menu-items a[data-depth] { padding-left: calc(14px + 16px * var(--d, 0)); }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"])::before { content: "→"; display: inline-block; margin-right: 6px; font-weight: 800; }

        /* mobile drawer */
        .m-drawer { position: fixed; inset: 0; z-index: 75; background: var(--accent); color: var(--ink); display: flex; flex-direction: column; justify-content: center; padding: 0 28px; opacity: 0; visibility: hidden; transition: opacity .25s ease, visibility .25s; }
        .m-drawer.open { opacity: 1; visibility: visible; }
        .m-drawer .mclose { position: absolute; top: 18px; right: 22px; background: var(--ink); color: var(--accent); border: 2.5px solid var(--ink); width: 44px; height: 44px; font-size: 22px; }
        .m-drawer .mtop { position: absolute; top: 22px; left: 28px; font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: 20px; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 8px; }
        .m-drawer nav a { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(28px, 10vw, 52px); line-height: 1.05; letter-spacing: -.02em; }
        .m-drawer nav a .ix { font-family: var(--body); font-size: 12px; font-weight: 700; vertical-align: super; margin-right: 10px; background: var(--ink); color: var(--accent); padding: 2px 6px; }
        .m-drawer .mfoot { position: absolute; bottom: 28px; left: 28px; right: 28px; display: flex; justify-content: space-between; font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; }

        /* toast */
        .toast { position: fixed; top: 22px; right: 22px; background: var(--accent); color: var(--ink); padding: 14px 18px; z-index: 100; font-family: var(--display); font-size: 12px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; border: 2.5px solid var(--ink); box-shadow: var(--pop); display: flex; align-items: center; gap: 10px; animation: toastIn .2s ease-out, toastOut .2s ease-in 3s forwards; }
        @keyframes toastIn { from { transform: translateX(1rem); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateX(1rem); } }

        /* section heading */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; margin: 72px 0 28px; gap: 16px; flex-wrap: wrap; }
        .sec-head h2 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(28px, 4vw, 52px); line-height: .95; letter-spacing: -.02em; }
        .sec-head a { font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; border: 2.5px solid var(--ink); padding: 8px 14px; background: var(--paper); box-shadow: var(--pop-sm); transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease; }
        .sec-head a:hover { background: var(--accent); transform: translate(-1px,-1px); box-shadow: var(--pop); }

        /* product grid + card */
        .pgrid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
        .pcard { cursor: pointer; position: relative; display: block; color: inherit; border: 2.5px solid var(--ink); background: var(--paper); box-shadow: var(--pop); transition: transform .14s ease, box-shadow .14s ease; }
        .pcard:hover { transform: translate(-2px, -2px); box-shadow: var(--pop-lg); }
        .pcard .imgwrap { position: relative; overflow: hidden; height: 320px; border-bottom: 2.5px solid var(--ink); }
        .pcard .imgwrap .img { position: absolute; inset: 0; }
        .pcard .imgwrap .img img { width: 100%; height: 100%; object-fit: cover; }
        .pcard .tag { position: absolute; top: 0; left: 0; background: var(--accent); border-right: 2.5px solid var(--ink); border-bottom: 2.5px solid var(--ink); font-family: var(--display); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; padding: 5px 10px; }
        .pcard .body { padding: 14px 16px; display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
        .pcard .nm { font-family: var(--display); font-weight: 700; font-size: 15px; line-height: 1.15; }
        .pcard .pr { font-family: var(--display); font-weight: 800; font-size: 15px; white-space: nowrap; background: var(--accent); border: 2px solid var(--ink); padding: 2px 7px; }
        @media (prefers-reduced-motion: reduce) { .pcard, .pcard:hover, .sec-head a:hover, .bag:hover { transform: none; } }

        /* page editorial header */
        .ed-head { border: 2.5px solid var(--ink); background: var(--accent); box-shadow: var(--pop); padding: 32px 30px; margin: 32px 0 36px; display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
        .ed-head .crumb { font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .ed-head h1 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: clamp(34px, 6vw, 76px); line-height: .9; margin-top: 8px; letter-spacing: -.03em; }
        .ed-head .meta { font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; max-width: 30ch; text-align: right; }

        /* footer */
        footer.site { border-top: 2.5px solid var(--ink); margin-top: 70px; background: var(--ink); color: var(--paper); padding: 50px 0 36px; }
        .fgrid { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 36px; }
        .fgrid .wm { font-family: var(--display); font-weight: 900; font-size: 32px; letter-spacing: -.02em; text-transform: uppercase; color: var(--accent); }
        .fcol h4 { font-family: var(--display); font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); margin-bottom: 14px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 9px; color: var(--paper); }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 44px; padding-top: 20px; border-top: 2px solid rgba(253,251,240,.25); font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; color: rgba(253,251,240,.7); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent); }

        @media (max-width: 1080px) { .pgrid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 680px) {
            .wrap { padding: 0 18px; }
            .nav .left { display: none; }
            .nav { grid-template-columns: auto 1fr auto; }
            .menu-toggle { display: inline-grid; place-items: center; }
            .pgrid { grid-template-columns: 1fr 1fr; gap: 14px; }
            .pcard .imgwrap { height: 220px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            .ed-head .meta { text-align: left; }
            .ed-head h1 { font-size: clamp(32px, 13vw, 60px); }
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
            // One repeating unit (text + diamond separator). The track holds two
            // identical halves, each = this unit repeated; the -50% translate then
            // loops seamlessly. JS measures a half and sets the duration from the
            // px/sec rate so perceived speed is length-independent.
            $tapeUnit = e($tape) . ' ✦ ';
            $tapeHalf = str_repeat($tapeUnit, 6);
            // Pre-JS fallback duration so it scrolls sensibly before measurement:
            // estimate a half-width as ~7.4px per char (Lexend Mega @12px, .08em
            // tracking) and divide by the chosen rate. Clamped to a sane band.
            $estWidth = max(320, (int) round(mb_strlen($tape) * 6 * 7.4));
            $pps = (int) $csAnnouncement['speed_px'];
            $fallbackDur = $pps > 0 ? max(6, min(120, (int) round($estWidth / $pps))) : 0;
        @endphp
        <div class="marquee-tape {{ $csAnnouncement['link'] ? 'link' : '' }}"
             data-marquee data-pps="{{ $pps }}"
             aria-label="{{ $tape }}"
             @if ($fallbackDur > 0) style="--tape-dur: {{ $fallbackDur }}s;" @endif>
            <div class="tape" aria-hidden="true">
                @if ($csAnnouncement['link'])
                    <a class="tape-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1" data-tape-unit="{{ $tapeUnit }}">{!! $tapeHalf !!}</a>
                    <a class="tape-half" href="{{ $csAnnouncement['link'] }}" tabindex="-1" aria-hidden="true">{!! $tapeHalf !!}</a>
                @else
                    <span class="tape-half" data-tape-unit="{{ $tapeUnit }}">{!! $tapeHalf !!}</span>
                    <span class="tape-half">{!! $tapeHalf !!}</span>
                @endif
            </div>
            @if ($csAnnouncement['link'])
                {{-- Reachable, non-animated link for keyboard/AT (the moving copies are aria-hidden). --}}
                <a href="{{ $csAnnouncement['link'] }}" class="sr-only">{{ $tape }}</a>
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
                    <a class="bag" href="/cart">
                        {{ __('site.common.cart') }} <span class="n">[{{ $cartCount }}]</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

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
        <div class="mfoot"><a href="/cart">{{ __('site.common.cart') }} [{{ $cartCount }}]</a><span>{{ $tenant->name }}</span></div>
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
                    <p style="color: rgba(253,251,240,.75); max-width: 32ch; margin-top: 14px; font-size: 14px;">
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
                <span>© {{ date('Y') }} {{ $tenant->name }} — {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        // Marquee tape — set animation duration from the merchant's px/sec rate
        // so perceived speed is the same regardless of text length. We measure
        // ONE tape half (the track is two halves; the loop translates -50%) and
        // set duration = halfWidth / pps. Re-runs on resize + after web fonts
        // load (Lexend Mega changes the measured width). data-pps="0" = static.
        (function () {
            var bar = document.querySelector('[data-marquee]');
            if (! bar) return;
            var pps = parseInt(bar.getAttribute('data-pps'), 10) || 0;
            if (pps <= 0) return; // static — CSS already disables the animation
            var half = bar.querySelector('.tape-half');
            if (! half) return;

            function apply() {
                var w = half.getBoundingClientRect().width;
                if (! w) return;
                // Clamp to a sane band so a tiny or huge string can't produce a
                // jittery (<5s) or near-frozen (>180s) loop.
                var dur = Math.max(5, Math.min(180, w / pps));
                bar.style.setProperty('--tape-dur', dur.toFixed(2) + 's');
            }

            apply();
            // Re-measure once the display font swaps in (metrics shift).
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(apply).catch(function () {});
            }
            // Debounced re-measure on resize.
            var t;
            window.addEventListener('resize', function () {
                clearTimeout(t);
                t = setTimeout(apply, 150);
            }, { passive: true });
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

        // Scroll reveal.
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.rv').forEach(function (el) { el.classList.add('rv-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e, i) {
                    if (e.isIntersecting) {
                        e.target.style.transitionDelay = Math.min(i * 45, 280) + 'ms';
                        e.target.classList.add('rv-in');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
