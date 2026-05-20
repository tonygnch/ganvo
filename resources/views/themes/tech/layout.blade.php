<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @php
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
            --primary-soft: color-mix(in srgb, {{ $store->primary_color }} 14%, white);
            --primary-strong: color-mix(in srgb, {{ $store->primary_color }} 80%, black);
            --secondary: {{ $store->secondary_color }};
            --bg: #f8fafc;
            --surface: #ffffff;
            --surface-2: #f1f5f9;
            --hair: #e2e8f0;
            --hair-strong: #cbd5e1;
            --text: #0f172a;
            --text-muted: #475569;
            --text-soft: #64748b;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, monospace;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "{{ $store->font_family }}", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }

        /* -------- Announce -------- */
        .announce {
            background: var(--text);
            color: var(--bg);
            text-align: center;
            padding: .5rem 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .announce a { text-decoration: underline; }

        /* -------- Header -------- */
        header.site {
            background: var(--surface);
            border-bottom: 1px solid var(--hair);
            position: sticky; top: 0; z-index: 50;
        }
        .site-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .brand {
            font-weight: 700;
            font-size: 1.0625rem;
            letter-spacing: -0.02em;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--text);
        }
        .brand img { height: 28px; }
        .brand .dot {
            width: 6px; height: 6px;
            border-radius: 2px;
            background: var(--primary);
            display: inline-block;
        }

        .nav { display: flex; gap: 1.25rem; align-items: center; }
        .nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: color .15s ease;
            padding: .5rem 0;
        }
        .nav-link:hover { color: var(--text); }
        .nav-right { display: flex; align-items: center; gap: 1rem; }

        /* Language dropdown */
        .lang-menu { position: relative; }
        .lang-menu summary {
            list-style: none; cursor: pointer;
            padding: .375rem .625rem;
            border-radius: 6px;
            border: 1px solid var(--hair);
            background: var(--surface-2);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            display: inline-flex; align-items: center; gap: .375rem;
            user-select: none;
            transition: border-color .15s ease;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { border-color: var(--hair-strong); color: var(--text); }
        .lang-menu .chevron { width: 8px; height: 8px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute; top: calc(100% + .375rem); right: 0;
            min-width: 140px;
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 8px;
            padding: .25rem;
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, .12);
            z-index: 60;
        }
        .lang-menu-items a {
            display: flex; align-items: center; justify-content: space-between;
            padding: .5rem .75rem;
            border-radius: 4px;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .lang-menu-items a:hover { background: var(--surface-2); color: var(--text); }
        .lang-menu-items a.active { color: var(--primary-strong); font-weight: 600; }
        .lang-menu-items .check { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }

        .cart-btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .5rem .875rem;
            border-radius: 8px;
            background: var(--text);
            color: var(--bg);
            font-size: 0.8125rem;
            font-weight: 600;
            transition: background-color .15s ease;
        }
        .cart-btn:hover { background: var(--primary); }
        .cart-count {
            background: var(--bg);
            color: var(--text);
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 700;
            min-width: 16px; height: 16px;
            line-height: 16px;
            text-align: center;
            padding: 0 5px;
        }
        .account-link {
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 500;
        }
        .account-link:hover { color: var(--text); }

        main { min-height: 60vh; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 1.5rem; }

        /* -------- Custom hero banner from store config -------- */
        .custom-hero {
            position: relative;
            padding: 5rem 1.5rem;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-soft), color-mix(in srgb, var(--secondary) 8%, var(--surface)));
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
            background: linear-gradient(180deg, rgba(15,23,42,.2) 0%, rgba(15,23,42,.65) 100%);
        }
        .custom-hero-inner {
            position: relative;
            max-width: 1280px;
            margin: 0 auto;
            z-index: 1;
        }
        .custom-hero h2 {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            margin: 0 0 1rem;
            line-height: 1.1;
        }
        .custom-hero p { font-size: 1.0625rem; max-width: 580px; margin: 0 0 1.5rem; }
        .custom-hero .cta {
            display: inline-block;
            padding: .75rem 1.5rem;
            background: var(--text);
            color: var(--bg);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: background-color .15s ease;
        }
        .custom-hero.with-image .cta { background: white; color: var(--text); }
        .custom-hero .cta:hover { background: var(--primary); color: white; }

        /* -------- Toast -------- */
        .toast {
            position: fixed; top: 1.25rem; right: 1.25rem;
            background: var(--text); color: var(--bg);
            padding: .75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            z-index: 100;
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, .25);
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        @keyframes toastIn { from { opacity: 0; transform: translateY(-.5rem); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-.5rem); } }

        /* -------- Footer -------- */
        footer.site {
            margin-top: 6rem;
            padding: 3rem 1.5rem 2rem;
            background: var(--text);
            color: var(--surface-2);
        }
        .footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2.5rem;
        }
        .footer-brand h3 { margin: 0 0 .5rem; color: white; font-weight: 700; font-size: 1.125rem; }
        .footer-brand p { color: rgba(255,255,255,.7); margin: 0; font-size: 0.875rem; max-width: 300px; }
        .footer-col h4 {
            margin: 0 0 .875rem;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .footer-col a {
            display: block; padding: .25rem 0;
            font-size: 0.8125rem;
            color: rgba(255,255,255,.7);
            transition: color .15s ease;
        }
        .footer-col a:hover { color: white; }
        .footer-bottom {
            max-width: 1280px;
            margin: 2.5rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,.1);
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: rgba(255,255,255,.5);
        }
        .footer-bottom a { color: rgba(255,255,255,.85); }
        .footer-bottom a:hover { color: white; }

        @media (max-width: 720px) {
            .footer-inner { grid-template-columns: 1fr 1fr; }
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
                @else
                    <span class="dot" aria-hidden="true"></span>
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
                        <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5"/></svg>
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
                        <a href="/account" class="account-link">{{ explode(' ', $customer->name)[0] }}</a>
                    @else
                        <a href="/account/login" class="account-link">{{ __('site.common.sign_in') }}</a>
                    @endif
                @endif
                <a href="/cart" class="cart-btn">
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
            <div class="footer-col">
                <h4>{{ __('site.storefront.footer.col_help') }}</h4>
                <a href="#">{{ __('site.storefront.value_props.shipping_title') }}</a>
                <a href="#">{{ __('site.storefront.value_props.returns_title') }}</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ date('Y') }} {{ $tenant->name }}</div>
            <div>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</div>
        </div>
    </footer>
</body>
</html>
