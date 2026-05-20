<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @php
        // Same dynamic Google Fonts loader as other themes — only ship the
        // font the merchant actually picked.
        $googleFonts = [
            'Inter'              => 'Inter:wght@400;500;600;700;800',
            'Roboto'             => 'Roboto:wght@400;500;700',
            'Lato'               => 'Lato:wght@400;700',
            'Merriweather'       => 'Merriweather:wght@400;700',
            'Playfair Display'   => 'Playfair+Display:wght@400;500;700',
            'Cormorant Garamond' => 'Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400',
        ];
        $fontSpec = $googleFonts[$store->font_family] ?? null;
    @endphp
    @if ($fontSpec)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ $fontSpec }}&display=swap" rel="stylesheet">
    @endif
    <style>
        :root {
            --primary: {{ $store->primary_color }};
            --primary-soft: color-mix(in srgb, {{ $store->primary_color }} 12%, white);
            --primary-strong: color-mix(in srgb, {{ $store->primary_color }} 80%, black);
            --secondary: {{ $store->secondary_color }};
            --bg: #fbfaf7;
            --surface: #ffffff;
            --muted: #f4f1ec;
            --hair: #e8e3da;
            --text: #1a1a1a;
            --text-muted: #5a5550;
            --text-soft: #9a948c;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "{{ $store->font_family }}", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }

        /* -------- Announce -------- */
        .announce {
            text-align: center;
            padding: .625rem 1.5rem;
            background: var(--text);
            color: var(--bg);
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        .announce a { border-bottom: 1px solid currentColor; }

        /* -------- Header -------- */
        header.site {
            border-bottom: 1px solid var(--hair);
            background: var(--bg);
            position: sticky; top: 0; z-index: 50;
        }
        .site-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        .brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            gap: .75rem;
        }
        .brand img { height: 32px; }
        .nav { display: flex; gap: 2rem; align-items: center; font-size: 0.875rem; }
        .nav-link {
            color: var(--text-muted);
            transition: color .15s ease;
            position: relative;
            padding-bottom: 2px;
        }
        .nav-link:hover { color: var(--text); }
        .nav-link::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: -2px;
            height: 1px; background: var(--text); transform: scaleX(0);
            transform-origin: left; transition: transform .25s ease;
        }
        .nav-link:hover::after { transform: scaleX(1); }
        .nav-right { display: flex; gap: 1.25rem; align-items: center; }

        /* Language dropdown — shared visual language with other themes. */
        .lang-menu { position: relative; }
        .lang-menu summary {
            list-style: none;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            letter-spacing: 0.06em;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            user-select: none;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { color: var(--text); }
        .lang-menu .chevron {
            width: 9px; height: 9px;
            fill: none; stroke: currentColor; stroke-width: 1.6;
            transition: transform .15s ease;
        }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute; top: calc(100% + .5rem); right: 0;
            min-width: 140px;
            background: var(--surface);
            border: 1px solid var(--hair);
            padding: .25rem;
            z-index: 60;
        }
        .lang-menu-items a {
            display: flex; align-items: center; justify-content: space-between;
            padding: .5rem .75rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            transition: background-color .12s ease, color .12s ease;
        }
        .lang-menu-items a:hover { background: var(--muted); color: var(--text); }
        .lang-menu-items a.active { color: var(--text); font-weight: 600; }
        .lang-menu-items .check { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }

        .cart-link {
            font-size: 0.8125rem;
            color: var(--text);
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-weight: 500;
            transition: color .15s ease;
        }
        .cart-link:hover { color: var(--primary); }
        .cart-count {
            background: var(--text);
            color: var(--bg);
            font-size: 0.625rem;
            font-weight: 700;
            min-width: 16px; height: 16px;
            line-height: 16px;
            border-radius: 999px;
            padding: 0 5px;
            text-align: center;
        }

        main { min-height: 60vh; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 2.5rem; }

        /* -------- Custom hero banner from store config -------- */
        .custom-hero {
            position: relative;
            min-height: 60vh;
            display: flex;
            align-items: center;
            padding: 4rem 2.5rem;
            overflow: hidden;
            color: var(--text);
        }
        .custom-hero.with-image { color: white; }
        .custom-hero .bg-img {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
        }
        .custom-hero .bg-img::after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.15) 0%, rgba(0,0,0,.6) 100%);
        }
        .custom-hero-inner { position: relative; max-width: 600px; z-index: 1; }
        .custom-hero h2 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.025em;
            margin: 0 0 1.25rem;
        }
        .custom-hero p {
            font-size: 1.125rem;
            line-height: 1.5;
            margin: 0 0 2rem;
            max-width: 480px;
        }
        .custom-hero .cta {
            display: inline-block;
            padding: 1rem 2rem;
            background: var(--text);
            color: var(--bg);
            font-size: 0.8125rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 600;
            transition: opacity .15s ease;
        }
        .custom-hero.with-image .cta { background: white; color: var(--text); }
        .custom-hero .cta:hover { opacity: .85; }

        /* -------- Toast -------- */
        .toast {
            position: fixed; top: 1.25rem; right: 1.25rem;
            background: var(--text); color: var(--bg);
            padding: .875rem 1.25rem;
            font-size: 0.875rem;
            z-index: 100;
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        @keyframes toastIn  { from { opacity: 0; transform: translateY(-.5rem); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-.5rem); } }

        /* -------- Footer -------- */
        footer.site {
            margin-top: 6rem;
            padding: 4rem 2.5rem 2.5rem;
            border-top: 1px solid var(--hair);
            background: var(--bg);
        }
        .footer-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
        }
        .footer-brand h3 { margin: 0 0 .75rem; font-size: 1.5rem; font-weight: 700; }
        .footer-brand p { color: var(--text-muted); margin: 0; max-width: 320px; font-size: 0.9375rem; }
        .footer-col h4 {
            margin: 0 0 1rem;
            font-size: 0.6875rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }
        .footer-col a {
            display: block;
            padding: .375rem 0;
            color: var(--text);
            font-size: 0.9375rem;
            transition: color .15s ease;
        }
        .footer-col a:hover { color: var(--primary); }
        .footer-bottom {
            max-width: 1400px;
            margin: 4rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid var(--hair);
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-soft);
        }
        .footer-bottom a { color: var(--text-muted); }
        .footer-bottom a:hover { color: var(--text); }

        @media (max-width: 720px) {
            .site-inner { padding: 1.25rem 1.25rem; }
            .container, .custom-hero { padding-left: 1.25rem; padding-right: 1.25rem; }
            .nav { gap: 1rem; }
            .footer-inner { grid-template-columns: 1fr; }
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
        $cartCount = \App\Services\Cart::forCurrent()->itemCount();
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        <div class="announce">
            @if ($csAnnouncement['link'])
                <a href="{{ $csAnnouncement['link'] }}">{{ $csAnnouncement['text'] }}</a>
            @else
                {{ $csAnnouncement['text'] }}
            @endif
        </div>
    @endif

    <header class="site">
        <div class="site-inner">
            <a href="/" class="brand">
                @if ($store->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="{{ $tenant->name }}">
                @endif
                <span>{{ $tenant->name }}</span>
            </a>
            <nav class="nav">
                @if (! empty($csNavMenu))
                    @foreach ($csNavMenu as $item)
                        <a href="{{ $item['url'] }}" class="nav-link">{{ $item['label'] }}</a>
                    @endforeach
                @else
                    <a href="/" class="nav-link">{{ __('site.storefront.nav.shop') }}</a>
                @endif
            </nav>
            <div class="nav-right">
                <details class="lang-menu">
                    <summary aria-label="{{ __('site.lang.switch') }}">
                        <span>{{ strtoupper($currentLocale) }}</span>
                        <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true">
                            <path d="M3 4.5L6 7.5L9 4.5"/>
                        </svg>
                    </summary>
                    <div class="lang-menu-items" role="menu">
                        @foreach ($languages as $code => $name)
                            <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">
                                <span>{{ $name }}</span>
                                <svg class="check" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10l4 4 8-8"/></svg>
                            </a>
                        @endforeach
                    </div>
                </details>
                @if ($store->showsAccountUi())
                    @if ($customer)
                        <a href="/account" class="cart-link">{{ explode(' ', $customer->name)[0] }}</a>
                    @else
                        <a href="/account/login" class="cart-link">{{ __('site.common.sign_in') }}</a>
                    @endif
                @endif
                <a href="/cart" class="cart-link">
                    <span>{{ __('site.common.cart') }}</span>
                    @if ($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif
                </a>
            </div>
        </div>
    </header>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site">
        <div class="footer-inner">
            <div class="footer-brand">
                <h3>{{ $tenant->name }}</h3>
                <p>{{ __('site.storefront.footer.tagline') }}</p>
            </div>
            <div class="footer-col">
                <h4>{{ __('site.storefront.footer.col_shop') }}</h4>
                <a href="/">{{ __('site.storefront.footer.all_products') }}</a>
                <a href="/cart">{{ __('site.common.cart') }}</a>
            </div>
            <div class="footer-col">
                <h4>{{ __('site.storefront.footer.col_help') }}</h4>
                <a href="#">{{ __('site.storefront.footer.shipping') }}</a>
                <a href="#">{{ __('site.storefront.footer.returns') }}</a>
                <a href="#">{{ __('site.storefront.footer.contact') }}</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ date('Y') }} {{ $tenant->name }}</div>
            <div>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</div>
        </div>
    </footer>
</body>
</html>
