<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @php
        // Map of merchant-pickable fonts → Google Fonts spec (without family= prefix).
        // Keep in sync with the curated list in App\Http\Controllers\Onboarding\WizardController::fonts().
        $googleFonts = [
            'Inter'              => 'Inter:wght@400;500;600;700;800',
            'Roboto'             => 'Roboto:wght@400;500;700',
            'Lato'               => 'Lato:wght@400;700',
            'Merriweather'       => 'Merriweather:wght@400;700',
            'Playfair Display'   => 'Playfair+Display:wght@400;500;700',
            'Cormorant Garamond' => 'Cormorant+Garamond:wght@400;500;600',
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
            --bg: #fafafa;
            --surface: #ffffff;
            --muted: #f5f5f4;
            --border: #e7e5e4;
            --text: #1c1917;
            --text-muted: #57534e;
            --text-soft: #a8a29e;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "{{ $store->font_family }}", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        a:hover { text-decoration: none; }

        /* -------- Announcement bar -------- */
        .announce {
            background: var(--secondary);
            color: white;
            text-align: center;
            font-size: 0.8125rem;
            padding: .5rem 1rem;
            letter-spacing: 0.04em;
            position: relative;
            overflow: hidden;
        }
        .announce-inner { display: inline-flex; align-items: center; gap: .5rem; }
        .announce strong { color: var(--primary); }

        /* -------- Header -------- */
        header.site {
            background: rgba(255,255,255,0.85);
            backdrop-filter: saturate(180%) blur(10px);
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid var(--border);
        }
        .site-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: var(--text);
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.01em;
        }
        .brand img { height: 32px; }
        .nav { display: flex; align-items: center; gap: 1.5rem; font-size: 0.925rem; color: var(--text-muted); }
        .nav a:hover { color: var(--primary); }
        .account-link {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            font-weight: 500;
        }
        .account-link svg {
            width: 16px; height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        /* -------- Language dropdown -------- */
        .lang-menu { position: relative; }
        .lang-menu summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            padding: .4rem .75rem;
            background: var(--muted);
            border-radius: 9999px;
            color: var(--text);
            font-size: 0.8125rem;
            font-weight: 600;
            transition: background-color .15s ease;
            user-select: none;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { background: var(--border); }
        .lang-menu[open] summary { background: var(--border); }
        .lang-menu .globe {
            width: 16px; height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .lang-menu .chevron {
            width: 12px; height: 12px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            transition: transform .15s ease;
        }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute;
            top: calc(100% + .375rem);
            right: 0;
            min-width: 160px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: .625rem;
            box-shadow: 0 12px 28px -8px rgba(0,0,0,0.15), 0 2px 6px rgba(0,0,0,0.05);
            padding: .375rem;
            z-index: 60;
        }
        .lang-menu-items a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .5rem .75rem;
            border-radius: .375rem;
            color: var(--text);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color .12s ease;
        }
        .lang-menu-items a:hover { background: var(--muted); }
        .lang-menu-items a.active { color: var(--primary); font-weight: 600; }
        .lang-menu-items .check {
            width: 14px; height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }
        .cart-btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem .875rem;
            border-radius: 9999px;
            background: var(--muted);
            color: var(--text);
            font-size: 0.875rem;
            font-weight: 600;
            transition: background-color .15s ease, color .15s ease;
        }
        .cart-btn:hover { background: var(--primary); color: white; }
        .cart-icon {
            width: 18px; height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .cart-count {
            background: var(--primary);
            color: white;
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 0.75rem;
            font-weight: 700;
            min-width: 18px;
            text-align: center;
        }
        .cart-btn:hover .cart-count { background: white; color: var(--primary); }

        /* -------- Page content slot -------- */
        main { min-height: 50vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
        .inner-page { padding: 3rem 1.5rem; max-width: 1100px; margin: 0 auto; }

        /* -------- Toast -------- */
        .toast {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            background: var(--text);
            color: white;
            padding: .875rem 1.25rem;
            border-radius: .75rem;
            box-shadow: 0 16px 32px -8px rgba(0,0,0,0.2);
            z-index: 100;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: .5rem;
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        .toast::before { content: "✓"; color: var(--primary); font-weight: 700; }
        @keyframes toastIn  { from { transform: translateY(-1rem); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-1rem); } }

        /* -------- Footer -------- */
        footer.site {
            background: var(--secondary);
            color: rgba(255,255,255,0.85);
            padding: 4rem 1.5rem 2rem;
            margin-top: 4rem;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .footer-brand h3 { margin: 0 0 .5rem; color: white; font-size: 1.25rem; }
        .footer-brand p { margin: 0; font-size: 0.875rem; max-width: 320px; }
        .footer-col h4 {
            margin: 0 0 .75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: white;
        }
        .footer-col a {
            display: block;
            padding: .25rem 0;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.7);
        }
        .footer-col a:hover { color: var(--primary); }
        .newsletter {
            margin-top: 1rem;
            display: flex;
            gap: .5rem;
        }
        .newsletter input {
            flex: 1;
            padding: .625rem .875rem;
            border-radius: .5rem;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.06);
            color: white;
            font: inherit;
            font-size: 0.875rem;
        }
        .newsletter input::placeholder { color: rgba(255,255,255,0.5); }
        .newsletter button {
            padding: .625rem 1.125rem;
            border-radius: .5rem;
            border: 0;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 3rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
            flex-wrap: wrap;
            gap: 1rem;
        }
        .footer-bottom a { color: var(--primary); }

        /* -------- Scroll reveal -------- */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .6s ease, transform .6s ease;
        }
        .reveal.in-view { opacity: 1; transform: translateY(0); }

        @media (max-width: 640px) {
            .site-inner { padding: .875rem 1rem; }
            .nav .nav-link { display: none; }
            .footer-inner { grid-template-columns: 1fr; gap: 2rem; }
            footer.site { padding: 3rem 1.25rem 1.5rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .001ms !important;
                transition-duration: .001ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="announce">
        <span class="announce-inner">
            <span>✨</span>
            <span>{!! __('site.storefront.announce', ['amount' => '<strong>$50</strong>', 'days' => 30]) !!}</span>
        </span>
    </div>

    <header class="site">
        <div class="site-inner">
            <a href="/" class="brand">
                @if ($store->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="{{ $tenant->name }}">
                @endif
                <span>{{ $tenant->name }}</span>
            </a>
            <nav class="nav">
                <a href="/" class="nav-link">{{ __('site.storefront.nav.shop') }}</a>
                <a href="#featured" class="nav-link">{{ __('site.storefront.nav.featured') }}</a>
                @php
                    $customer = auth('customer')->user();
                    $currentLocale = app()->getLocale();
                    $languages = \App\Http\Middleware\SetLocale::available();
                    $supportedCurrencies = $store->supportedDisplayCurrencies();
                @endphp
                <details class="lang-menu">
                    <summary aria-label="{{ __('site.lang.switch') }}">
                        <svg class="globe" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M3 12h18"/>
                            <path d="M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
                        </svg>
                        <span>{{ strtoupper($currentLocale) }}</span>
                        <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true">
                            <path d="M3 4.5L6 7.5L9 4.5"/>
                        </svg>
                    </summary>
                    <div class="lang-menu-items" role="menu">
                        @foreach ($languages as $code => $name)
                            <a role="menuitem" href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">
                                <span>{{ $name }}</span>
                                <svg class="check" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M4 10l4 4 8-8"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </details>
                @if (count($supportedCurrencies) > 1)
                    <details class="lang-menu">
                        <summary aria-label="{{ __('site.currency.switch') }}">
                            <svg class="globe" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M14.5 8.5a3 3 0 0 0-2.5-1.5h-.5a2.5 2.5 0 0 0 0 5h1a2.5 2.5 0 0 1 0 5h-.5a3 3 0 0 1-2.5-1.5"/>
                                <path d="M12 5v2M12 17v2"/>
                            </svg>
                            <span>{{ $displayCurrency }}</span>
                            <svg class="chevron" viewBox="0 0 12 12" aria-hidden="true">
                                <path d="M3 4.5L6 7.5L9 4.5"/>
                            </svg>
                        </summary>
                        <div class="lang-menu-items" role="menu">
                            @foreach ($supportedCurrencies as $code)
                                <a role="menuitem" href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif">
                                    <span>{{ \App\Services\Money::symbol($code) }} · {{ $code }}</span>
                                    <svg class="check" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M4 10l4 4 8-8"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
                @if ($store->showsAccountUi())
                    @if ($customer)
                        <a href="/account" class="nav-link account-link" title="{{ __('site.common.my_account') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                            <span>{{ __('site.common.hi_name', ['name' => explode(' ', $customer->name)[0]]) }}</span>
                        </a>
                    @else
                        <a href="/account/login" class="nav-link account-link">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                            <span>{{ __('site.common.sign_in') }}</span>
                        </a>
                    @endif
                @endif
                @php $cartCount = \App\Services\Cart::forCurrent()->itemCount(); @endphp
                <a href="/cart" class="cart-btn">
                    <svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 4h2l2.4 12.5a2 2 0 0 0 2 1.5h8.4a2 2 0 0 0 2-1.6L21 8H6"/>
                        <circle cx="10" cy="20" r="1.6"/>
                        <circle cx="18" cy="20" r="1.6"/>
                    </svg>
                    <span>{{ __('site.common.cart') }}</span>
                    @if ($cartCount > 0)
                        <span class="cart-count">{{ $cartCount }}</span>
                    @endif
                </a>
            </nav>
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
                <form class="newsletter"
                      data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}"
                      onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                    <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                    <button type="submit">{{ __('site.storefront.footer.subscribe') }}</button>
                </form>
            </div>
            <div class="footer-col">
                <h4>{{ __('site.storefront.footer.col_shop') }}</h4>
                <a href="/">{{ __('site.storefront.footer.all_products') }}</a>
                <a href="/#featured">{{ __('site.storefront.nav.featured') }}</a>
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
            <div>© {{ date('Y') }} {{ $tenant->name }}. {{ __('site.common.all_rights') }}</div>
            <div>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener">Ganvo</a>']) !!}</div>
        </div>
    </footer>

    <script>
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in-view'); io.unobserve(e.target); } });
            }, { threshold: 0.1, rootMargin: '0px 0px -8% 0px' });
            document.querySelectorAll('.reveal').forEach(el => io.observe(el));
        } else {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('in-view'));
        }
    </script>
</body>
</html>
