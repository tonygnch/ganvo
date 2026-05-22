<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @php
        // Menu theme pairs a serif display face (Playfair Display) with
        // the merchant's chosen body font. The serif is ALWAYS loaded for
        // section headings; the merchant's pick is used for body text.
        $googleFonts = [
            'Inter'              => 'Inter:wght@400;500;600;700;800',
            'Roboto'             => 'Roboto:wght@400;500;700',
            'Lato'               => 'Lato:wght@400;700',
            'Merriweather'       => 'Merriweather:wght@400;700',
            'Playfair Display'   => 'Playfair+Display:wght@400;500;700',
            'Cormorant Garamond' => 'Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400',
        ];
        $body = $googleFonts[$store->font_family] ?? null;
        $serif = $googleFonts['Playfair Display'];
        $families = $body && $store->font_family !== 'Playfair Display'
            ? $serif . '&family=' . $body
            : $serif;
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $families }}&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ $store->primary_color }};
            --primary-strong: color-mix(in srgb, {{ $store->primary_color }} 80%, black);
            --secondary: {{ $store->secondary_color }};
            --paper: #fcf7ec;     /* warm cream — the menu's base */
            --paper-deep: #f0e7d3;
            --ink: #2a1d10;       /* warm dark brown */
            --ink-soft: #5e4a32;
            --rule: #d8c8a8;
            --accent: var(--primary);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "{{ $store->font_family }}", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--ink);
            background: var(--paper);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .serif { font-family: 'Playfair Display', Georgia, serif; }

        /* -------- Announce -------- */
        .announce {
            background: var(--ink);
            color: var(--paper);
            text-align: center;
            padding: .625rem 1.25rem;
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        /* -------- Header -------- */
        header.site {
            padding: 2.5rem 1.5rem 2rem;
            text-align: center;
            border-bottom: 2px solid var(--ink);
            background: var(--paper);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: 2.5rem;
            letter-spacing: -0.01em;
            line-height: 1;
            color: var(--ink);
            margin: 0 0 1.5rem;
        }
        .brand img { height: 44px; }
        .nav {
            display: flex;
            justify-content: center;
            gap: 2rem;
            font-size: 0.75rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--ink-soft);
        }
        .nav a:hover { color: var(--ink); }
        .nav-utils {
            display: flex;
            justify-content: center;
            gap: 1.25rem;
            margin-top: 1.25rem;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
        }
        .nav-utils a:hover { color: var(--ink); }
        .nav-utils .sep { color: var(--rule); }
        .cart-count {
            background: var(--ink);
            color: var(--paper);
            font-size: 0.625rem;
            padding: 1px 6px;
            border-radius: 999px;
            margin-left: .25rem;
            letter-spacing: 0;
        }

        /* Language switcher */
        .lang-menu { position: relative; display: inline-flex; }
        .lang-menu summary {
            list-style: none; cursor: pointer;
            display: inline-flex; gap: .25rem; align-items: center;
            user-select: none;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { color: var(--ink); }
        .lang-menu .chevron { width: 8px; height: 8px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute; top: calc(100% + .5rem); left: 50%; transform: translateX(-50%);
            min-width: 140px;
            background: var(--paper);
            border: 1px solid var(--rule);
            padding: .25rem;
            z-index: 50;
        }
        .lang-menu-items a {
            display: flex; align-items: center; justify-content: space-between;
            padding: .5rem .75rem;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-soft);
        }
        .lang-menu-items a:hover { background: var(--paper-deep); color: var(--ink); }
        .lang-menu-items a.active { color: var(--ink); font-weight: 600; }
        .lang-menu-items .check { width: 11px; height: 11px; fill: none; stroke: currentColor; stroke-width: 2; }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }

        main { min-height: 60vh; }

        /* -------- Custom hero banner from store config -------- */
        .custom-hero {
            position: relative;
            padding: 4rem 1.5rem;
            text-align: center;
            color: var(--ink);
            overflow: hidden;
        }
        .custom-hero.with-image { color: white; padding: 6rem 1.5rem; }
        .custom-hero .bg-img {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
        }
        .custom-hero .bg-img::after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.2) 0%, rgba(0,0,0,.55) 100%);
        }
        .custom-hero-inner { position: relative; max-width: 600px; margin: 0 auto; z-index: 1; }
        .custom-hero h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-style: italic;
            font-size: clamp(2rem, 4.5vw, 3rem);
            line-height: 1.1;
            margin: 0 0 1rem;
        }
        .custom-hero p {
            font-size: 1.0625rem;
            margin: 0 0 1.5rem;
            line-height: 1.5;
        }
        .custom-hero .cta {
            display: inline-block;
            padding: .75rem 1.75rem;
            background: var(--ink);
            color: var(--paper);
            font-size: 0.75rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .custom-hero.with-image .cta { background: white; color: var(--ink); }

        /* -------- Toast -------- */
        .toast {
            position: fixed; top: 1.25rem; left: 50%;
            transform: translateX(-50%);
            background: var(--ink); color: var(--paper);
            padding: .75rem 1.25rem;
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            z-index: 100;
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        @keyframes toastIn { from { opacity: 0; transform: translate(-50%, -.5rem); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes toastOut { to { opacity: 0; } }

        /* -------- Footer -------- */
        footer.site {
            margin-top: 6rem;
            padding: 3rem 1.5rem;
            text-align: center;
            border-top: 2px solid var(--ink);
            background: var(--paper);
        }
        .footer-brand {
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 1.5rem;
            color: var(--ink);
            margin: 0 0 1rem;
        }
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 1.5rem;
        }
        .footer-links a:hover { color: var(--ink); }
        .footer-bottom {
            font-size: 0.6875rem;
            color: var(--ink-soft);
            letter-spacing: 0.08em;
            padding-top: 1.5rem;
            border-top: 1px solid var(--rule);
            max-width: 700px;
            margin: 0 auto;
        }
        .footer-bottom a { color: var(--ink); }
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
                <a href="{{ $csAnnouncement['link'] }}" style="color: inherit; border-bottom: 1px solid currentColor;">{{ $csAnnouncement['text'] }}</a>
            @else
                {{ $csAnnouncement['text'] }}
            @endif
        </div>
    @endif

    <header class="site">
        <a href="/" class="brand">
            @if ($store->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="{{ $tenant->name }}">
            @endif
            <span>{{ $tenant->name }}</span>
        </a>
        @if (! empty($csNavMenu))
            <nav class="nav">
                @foreach ($csNavMenu as $item)
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        @endif
        <div class="nav-utils">
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
            <span class="sep">·</span>
            @if ($store->showsAccountUi())
                @if ($customer)
                    <a href="/account">{{ explode(' ', $customer->name)[0] }}</a>
                @else
                    <a href="/account/login">{{ __('site.common.sign_in') }}</a>
                @endif
                <span class="sep">·</span>
            @endif
            <a href="/cart">{{ __('site.common.cart') }}@if($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif</a>
        </div>
    </header>

    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="site">
        <div class="footer-brand">{{ $tenant->name }}</div>
        <div class="footer-links">
            <a href="/">{{ __('site.storefront.footer.all_products') }}</a>
            <a href="/cart">{{ __('site.common.cart') }}</a>
            <a href="#">{{ __('site.storefront.footer.contact') }}</a>
        </div>
        <div class="footer-bottom">
            © {{ date('Y') }} {{ $tenant->name }} ·
            {!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}
        </div>
    </footer>
</body>
</html>
