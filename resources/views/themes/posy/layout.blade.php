<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Posy hard-codes its typography: DM Serif Display (editorial display) +
         Cormorant Garamond (delicate italic accents) + Hanken Grotesk (body).
         A soft, seasonal florist pairing. The merchant's font_family setting is
         intentionally ignored, like the other curated themes. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500;1,600&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Default is a soft sage green. */
            --accent: {{ $store->primary_color ?: '#4a6b3c' }};
            --display: "DM Serif Display", serif;
            --serif: "Cormorant Garamond", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --bg: #eef0e4;
            --ink: #28321f;
            --soft: #e2e6d4;
            --soft2: #d6dcc3;
            --card: #fbfcf5;
            --line: #d2d8c2;
            --muted: #79806c;
            --deep: #222b1a;
            --leaf: linear-gradient(160deg, #5d7a44, #2f4127);
            --bloom: linear-gradient(160deg, #cf8f6e, #9c5a3e);
            --tape: rgba(120, 140, 90, .28);

            --header-height: 76px;

            /* Legacy aliases so the shared partials (catalog-controls,
               variant-picker, collection-strips, pagination, stripe-payment…)
               render in the Posy palette. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--card));
            --primary-strong: color-mix(in srgb, var(--accent) 82%, #000);
            --secondary: var(--ink);
            --surface: var(--card);
            --border: var(--line);
            --text: var(--ink);
            --text-muted: var(--muted);
            --text-soft: var(--muted);

            /* Variant picker: soft pill chip, accent outline when selected. */
            --vp-radius: 99px;
            --vp-fill: var(--accent);
            --vp-on-accent: #fbfcf5;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; overflow-x: hidden; }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1220px; margin: 0 auto; padding: 0 40px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* placeholder fills (used wherever a real image is missing) */
        .ph { position: relative; background: var(--leaf); overflow: hidden; }
        .bloomph { background: var(--bloom); }
        .ph img, .bloomph img, .bcard .pic img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .kicker { font-family: var(--body); font-size: 12px; letter-spacing: .18em; text-transform: uppercase; font-weight: 600; color: var(--accent); }

        /* buttons — pill, accent fill / outline */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-size: 14px; font-weight: 600; padding: 15px 30px; border: 1px solid var(--accent); background: var(--accent); color: #fbfcf5; border-radius: 99px; transition: filter .25s ease, transform .25s ease, background-color .25s ease, color .25s ease; }
        .btn:hover { filter: brightness(1.08); transform: translateY(-2px); }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover { background: var(--ink); color: var(--bg); filter: none; }
        .btn.block { width: 100%; }
        .btn:disabled { opacity: .55; cursor: not-allowed; transform: none; filter: none; }
        @media (prefers-reduced-motion: reduce) { .btn:hover { transform: none; } }

        /* washi tape strip — florist signature on cards */
        .tape { position: absolute; width: 88px; height: 26px; background: var(--tape); backdrop-filter: blur(1px); transform: rotate(-4deg); top: -12px; left: 50%; margin-left: -44px; z-index: 5; border-radius: 1px; box-shadow: 0 2px 6px rgba(0, 0, 0, .06); pointer-events: none; }
        .tape.r { transform: rotate(5deg); }

        /* reveal on scroll */
        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .9s ease, transform 1s cubic-bezier(.19, .7, .16, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .reveal.s1 { transition-delay: .1s; } .reveal.s2 { transition-delay: .2s; } .reveal.s3 { transition-delay: .3s; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none !important; } .floaty, .tick .track { animation: none !important; } }

        /* ticker — the announcement bar, soft scrolling strip */
        .tick { background: var(--deep); color: #e2e6d4; overflow: hidden; white-space: nowrap; }
        .tick .track { display: inline-flex; gap: 30px; padding: 10px 0; animation: tick var(--tick-dur, 36s) linear infinite; font-size: 12px; letter-spacing: .1em; will-change: transform; }
        .tick .track .s { color: #9bb37f; }
        .tick.link a { color: inherit; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .tick[data-static="1"] .track { animation: none; }
        @media (prefers-reduced-motion: reduce) { .tick .track { animation-play-state: paused; } }

        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }

        /* header / nav */
        header.site { position: sticky; top: 0; z-index: 60; background: rgba(238, 240, 228, .88); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; gap: 34px; height: 76px; }
        .logo { font-family: var(--display); font-size: 28px; color: var(--ink); white-space: nowrap; }
        .logo img { height: 34px; width: auto; display: block; }
        .nav .links { display: flex; gap: 26px; font-size: 14.5px; align-items: center; }
        .nav .links a:hover { color: var(--accent); }
        .nav .right { margin-left: auto; display: flex; gap: 22px; align-items: center; font-size: 14.5px; }
        .nav .right a:hover { color: var(--accent); }
        .bag .n { background: var(--accent); color: #fbfcf5; min-width: 19px; height: 19px; padding: 0 5px; border-radius: 99px; font-size: 11px; display: inline-grid; place-items: center; margin-left: 5px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; z-index: 80; color: var(--ink); }

        /* soft dropdown (lang / currency / nav groups) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); font-size: 14.5px; user-select: none; }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary:hover { color: var(--accent); }
        .menu .chev { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 1.6; transition: transform .2s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); right: 0; min-width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 8px; z-index: 70; box-shadow: 0 24px 50px -28px rgba(40, 50, 31, .5); }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 9px; font-size: 14px; color: var(--ink); }
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
        .m-drawer .mlogo { position: absolute; top: 24px; left: 40px; font-family: var(--display); font-size: 24px; }
        .m-drawer nav { display: flex; flex-direction: column; gap: 2px; }
        .m-drawer nav a { font-family: var(--display); font-size: 40px; padding: 6px 0; }
        .m-drawer nav a em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .m-drawer .mfoot { position: absolute; bottom: 34px; font-size: 12px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }

        /* section + page heads (shared by inner pages) */
        .sec-head { text-align: center; margin: 80px 0 40px; }
        .sec-head .kicker { display: block; margin-bottom: 10px; }
        .sec-head h2 { font-family: var(--display); font-size: clamp(30px, 4vw, 50px); font-weight: 400; }
        .sec-head h2 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .sec-head .more { display: inline-block; margin-top: 14px; font-size: 13px; color: var(--accent); border-bottom: 1px solid currentColor; padding-bottom: 1px; }
        .page-head { text-align: center; padding: 48px 0 26px; }
        .page-head .crumb { font-size: 12px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .page-head .crumb a:hover { color: var(--accent); }
        .page-head h1 { font-family: var(--display); font-size: clamp(40px, 5vw, 64px); margin-top: 8px; font-weight: 400; }
        .page-head h1 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .page-head p { color: var(--muted); max-width: 48ch; margin: 10px auto 0; }

        /* product card — polaroid (used on home, catalog, collection, related) */
        .blooms { display: grid; grid-template-columns: repeat(3, 1fr); gap: 34px 26px; padding-top: 14px; }
        .bcard { display: block; color: inherit; background: var(--card); border-radius: 6px; padding: 14px 14px 20px; cursor: pointer; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4); position: relative; transition: transform .3s cubic-bezier(.19, .7, .16, 1), box-shadow .3s ease; }
        .bcard:nth-child(3n+1) { transform: rotate(-1.6deg); }
        .bcard:nth-child(3n+2) { transform: rotate(1deg); }
        .bcard:nth-child(3n) { transform: rotate(-0.6deg); }
        .bcard:hover { transform: rotate(0) translateY(-6px); box-shadow: 0 30px 56px -26px rgba(40, 50, 31, .5); }
        .bcard .pic { height: 280px; border-radius: 3px; margin-bottom: 14px; position: relative; overflow: hidden; }
        .bcard .badge { position: absolute; top: 12px; left: 12px; background: var(--card); font-size: 11px; padding: 4px 10px; border-radius: 99px; color: var(--accent); font-weight: 600; z-index: 2; }
        .bcard .cat { font-size: 12px; color: var(--muted); text-align: center; }
        .bcard h3 { font-family: var(--display); font-size: 23px; text-align: center; margin: 2px 0 4px; font-weight: 400; }
        .bcard .pr { font-family: var(--body); font-weight: 600; font-size: 20px; font-variant-numeric: tabular-nums; text-align: center; color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .bcard, .bcard:hover { transform: none; } }

        /* footer */
        footer.site { background: var(--soft); padding: 62px 0 34px; margin-top: 90px; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-size: 24px; }
        .fcol h4 { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: #55604a; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); gap: 16px; flex-wrap: wrap; }
        .fbot a { color: var(--accent); }

        /* toast */
        .toast { position: fixed; top: calc(var(--header-height) + 22px); right: 24px; background: var(--card); color: var(--ink); padding: 14px 20px; z-index: 100; font-size: 14px; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 24px 50px -28px rgba(40, 50, 31, .5); display: flex; align-items: center; gap: 10px; animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards; }
        .toast::before { content: "❧"; color: var(--accent); }
        @keyframes toastIn { from { transform: translateY(-8px); opacity: 0; } to { transform: none; opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-8px); } }

        @media (max-width: 1000px) {
            .blooms { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 680px) {
            .wrap { padding: 0 20px; }
            .nav .links { display: none; }
            .menu-toggle { display: block; }
            .blooms { grid-template-columns: 1fr; }
            .bcard { transform: none !important; }
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
            $tapeUnit = e($tape) . ' &nbsp;<span class="s">✿</span>&nbsp; ';
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
                <a class="logo" href="/">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{{ $tenant->name }}@endif
                </a>
                <div class="links">
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
