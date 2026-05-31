<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Atelier hard-codes its typography — the design depends on Cormorant
         Garamond display + Hanken Grotesk body. The merchant's font_family
         setting is intentionally ignored for this theme. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Marcellus&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* The one merchant-controllable knob: brand accent maps to
               primary_color. Everything else is the Atelier palette. */
            --accent: {{ $store->primary_color ?: '#1a1a1a' }};
            --display: "Cormorant Garamond", serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --ink: #16140f;
            --paper: #f4f2ee;
            --soft: #e8e3da;
            --soft2: #ded7cc;
            --line: #d8d2c7;
            --muted: #8b8478;

            /* Aliases — shared storefront pages (cart, checkout, order, auth)
               reference the legacy default-theme tokens. Mapping them here
               keeps those pages rendering coherently with Atelier without
               a per-page rewrite. */
            --primary: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 12%, var(--paper));
            --primary-strong: var(--accent);
            --secondary: var(--ink);
            --bg: var(--paper);
            --surface: #ffffff;
            --muted: #8b8478;
            --border: var(--line);
            --text: var(--ink);
            --text-muted: #4f4a40;
            --text-soft: var(--muted);
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
        }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 40px; }
        .serif { font-family: var(--display); }

        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        /* placeholder (used by partials where image is missing) */
        .ph {
            position: relative;
            background: var(--soft);
            background-image: repeating-linear-gradient(45deg, rgba(22,20,15,.05) 0 10px, transparent 10px 20px);
            display: grid;
            place-items: center;
            overflow: hidden;
        }
        .ph span {
            font-family: "Hanken Grotesk", monospace;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            background: rgba(244,242,238,.7);
            padding: 5px 11px;
            border-radius: 2px;
        }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        /* buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-size: 13px;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 600;
            padding: 15px 30px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            transition: .2s;
        }
        .btn:hover { opacity: .84; }
        .btn.outline {
            background: transparent;
            color: var(--ink);
            border-color: var(--ink);
        }
        .btn.outline:hover { background: var(--ink); color: var(--paper); opacity: 1; }
        .btn.block { width: 100%; }

        /* marquee (top announcement strip) */
        .marquee {
            background: var(--ink);
            color: var(--paper);
            text-align: center;
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            padding: 9px;
        }
        .marquee a { color: inherit; border-bottom: 1px solid rgba(244,242,238,.4); padding-bottom: 1px; }

        /* header */
        header.site {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(244,242,238,.86);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .nav {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            height: 74px;
        }
        .nav .left, .nav .right {
            display: flex;
            gap: 26px;
            align-items: center;
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .nav .right { justify-content: flex-end; }
        .nav a.lk {
            color: var(--ink);
            position: relative;
            padding: 4px 0;
        }
        .nav a.lk:hover { color: var(--ink); }
        .nav a.lk::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -1px;
            height: 1px;
            width: 0;
            background: currentColor;
            transition: width .35s cubic-bezier(.19,.7,.16,1);
        }
        .nav a.lk:hover::after { width: 100%; }
        .logo {
            font-family: var(--display);
            font-size: 30px;
            letter-spacing: .34em;
            text-transform: uppercase;
            text-align: center;
            padding-left: .34em;
            color: var(--ink);
            white-space: nowrap;
        }
        .logo img { height: 36px; width: auto; display: inline-block; }
        .bag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .bag .n {
            background: var(--accent);
            color: #fff;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 10px;
            display: grid;
            place-items: center;
            font-family: var(--body);
        }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; }

        /* language / currency dropdowns — minimal, fit the Atelier nav style */
        .menu { position: relative; }
        .menu summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--ink);
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
            user-select: none;
            padding: 4px 0;
            position: relative;
        }
        .menu summary::-webkit-details-marker, .menu summary::marker { display: none; content: none; }
        .menu summary::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -1px;
            height: 1px;
            width: 0;
            background: currentColor;
            transition: width .35s cubic-bezier(.19,.7,.16,1);
        }
        .menu:hover summary::after, .menu[open] summary::after { width: 100%; }
        .menu .chev { width: 10px; height: 10px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            min-width: 190px;
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 6px;
            z-index: 60;
            box-shadow: 0 20px 40px -16px rgba(22,20,15,.18);
        }
        .menu-items a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            color: var(--ink);
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            transition: background-color .12s ease, color .12s ease;
        }
        .menu-items a:hover { background: var(--soft); }
        .menu-items a.active { color: var(--accent); }
        .menu-items .check {
            width: 13px; height: 13px;
            fill: none; stroke: var(--accent);
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .menu-items a:not(.active) .check { visibility: hidden; }

        /* Nav dropdown — flyout under a top-level header item that has
           configured children. Wider than the lang/currency popovers to
           fit category names comfortably; "View all" entry rendered
           prominent so the parent's own page stays one click away. */
        .menu.nav-menu .menu-items {
            right: auto;
            left: 0;
            min-width: 230px;
        }
        .menu.nav-menu .menu-items a {
            justify-content: flex-start;
            gap: 8px;
        }
        .menu.nav-menu .menu-items a.view-all {
            color: var(--ink);
            font-weight: 700;
            border-bottom: 1px solid var(--line);
            margin-bottom: 4px;
            padding-bottom: 12px;
        }
        .menu.nav-menu .menu-items a.view-all:hover { color: var(--accent); }

        /* Hierarchy indent — child categories get a small left padding
           scaled by depth, plus a "└" leader to read as nested in a
           single flat dropdown without needing real submenus. */
        .menu.nav-menu .menu-items a[data-depth] { padding-left: calc(12px + 18px * var(--d, 0)); }
        .menu.nav-menu .menu-items a[data-depth]::before {
            content: "";
            display: inline-block;
        }
        .menu.nav-menu .menu-items a[data-depth]:not([data-depth="0"])::before {
            content: "└";
            display: inline-block;
            margin-right: 4px;
            color: var(--muted);
            font-weight: 400;
        }

        /* toast */
        .toast {
            position: fixed;
            top: 24px;
            right: 24px;
            background: var(--ink);
            color: var(--paper);
            padding: 14px 18px;
            z-index: 100;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 20px 40px -10px rgba(22,20,15,.3);
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        .toast::before {
            content: "";
            display: inline-block;
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--accent);
        }
        @keyframes toastIn  { from { transform: translateY(-1rem); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-1rem); } }

        /* scroll/transition reveal */
        .rv { opacity: 0; transform: translateY(26px); }
        .rv.rv-in {
            opacity: 1;
            transform: none;
            transition: opacity .8s ease, transform .9s cubic-bezier(.19,.7,.16,1);
        }
        @media (prefers-reduced-motion: reduce) {
            .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; }
        }

        /* section heading */
        .sec-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin: 84px 0 28px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 18px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .sec-head h2 {
            font-family: var(--display);
            font-size: clamp(30px, 3.6vw, 46px);
            font-weight: 500;
        }
        .sec-head a {
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .sec-head a:hover { color: var(--ink); }

        /* product grid + product card (used by index/category/collection/PDP-related) */
        .pgrid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px 22px;
        }
        .pcard {
            cursor: pointer;
            overflow: hidden;
            display: block;
            color: inherit;
        }
        .pcard .img {
            height: 360px;
            margin-bottom: 14px;
            transition: transform .55s cubic-bezier(.19,.7,.16,1), opacity .3s;
        }
        .pcard:hover .img { opacity: .9; transform: scale(1.035); }
        .pcard .nm { font-size: 15px; font-weight: 500; }
        .pcard .pr { font-size: 14px; color: var(--muted); margin-top: 3px; }
        .pcard .tag {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--paper);
            font-size: 10px;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 5px 9px;
        }

        /* footer */
        footer.site {
            border-top: 1px solid var(--line);
            margin-top: 100px;
            padding: 70px 0 40px;
        }
        .fgrid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 40px;
        }
        .fgrid .logo {
            text-align: left;
            padding: 0;
            font-size: 26px;
        }
        .fcol h4 {
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 18px;
        }
        .fcol a {
            display: block;
            font-size: 14px;
            margin-bottom: 11px;
            color: #4f4a40;
        }
        .fcol a:hover { color: var(--ink); }
        .fbot {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: var(--muted);
            gap: 16px;
            flex-wrap: wrap;
        }
        .fbot a { color: inherit; border-bottom: 1px solid currentColor; padding-bottom: 1px; }

        /* responsive */
        @media (max-width: 1000px) {
            .pgrid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 720px) {
            .wrap { padding: 0 20px; }
            .nav .left, .nav a.lk:not(.logo) { display: none; }
            .nav .right { gap: 18px; }
            .nav { grid-template-columns: auto 1fr auto; }
            .menu-toggle { display: block; }
            .logo { text-align: left; font-size: 24px; padding-left: 0; letter-spacing: .24em; }
            .pgrid { gap: 16px; }
            .pcard .img { height: 260px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
            footer.site { padding: 50px 0 30px; }
            .sec-head { margin: 60px 0 22px; }
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
                                {{-- Dropdown parent. Uses the same <details> pattern as
                                     the language/currency menus so behavior + a11y stay
                                     consistent. If the parent ALSO has a URL, the dropdown
                                     gets a "View all" link as its first entry. --}}
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

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    @yield('content')

    <footer class="site">
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="logo">{{ $tenant->name }}</div>
                    <p style="color: var(--muted); max-width: 30ch; margin-top: 16px; font-size: 14px;">
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
        // Scroll-driven reveal (simpler than the original template's per-screen
        // stagger since each page is a real navigation, not a SPA screen swap).
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.rv').forEach(el => el.classList.add('rv-in'));
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
    </script>
</body>
</html>
