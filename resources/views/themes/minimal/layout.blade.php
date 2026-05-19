<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    @php
        // Always load Cormorant Garamond — the minimal theme uses it for
        // serif headings throughout, independent of the merchant's body
        // font choice. If the body font is different, load it too.
        $googleFonts = [
            'Inter'              => 'Inter:wght@400;500;600;700;800',
            'Roboto'             => 'Roboto:wght@400;500;700',
            'Lato'               => 'Lato:wght@400;700',
            'Merriweather'       => 'Merriweather:wght@400;700',
            'Playfair Display'   => 'Playfair+Display:wght@400;500;700',
            'Cormorant Garamond' => 'Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400',
        ];
        $bodyFont = $googleFonts[$store->font_family] ?? null;
        $cormorant = $googleFonts['Cormorant Garamond'];
        $families = $bodyFont && $store->font_family !== 'Cormorant Garamond'
            ? $cormorant . '&family=' . $bodyFont
            : $cormorant;
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $families }}&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ $store->primary_color }};
            --secondary: {{ $store->secondary_color }};
            --bg: #ffffff;
            --surface: #ffffff;
            --muted: #fafaf9;
            --hair: #e7e5e4;
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
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { color: inherit; text-decoration: none; }
        .serif { font-family: 'Cormorant Garamond', Georgia, serif; }

        /* -------- Announcement bar -------- */
        .announce {
            background: var(--text);
            color: white;
            text-align: center;
            font-size: 0.6875rem;
            padding: .625rem 1rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        /* -------- Header -------- */
        header.site {
            border-bottom: 1px solid var(--hair);
            background: var(--bg);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .site-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 2rem;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.5rem;
        }
        .nav-left, .nav-right { display: flex; align-items: center; gap: 1.5rem; }
        .nav-right { justify-content: flex-end; }
        .nav-link {
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            transition: color .15s ease;
        }
        .nav-link:hover { color: var(--text); }
        .nav-link.active { color: var(--text); }
        .brand {
            text-align: center;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: 1.75rem;
            letter-spacing: 0.04em;
            color: var(--text);
            line-height: 1;
        }
        .brand img { max-height: 36px; margin-bottom: .5rem; display: block; margin-left: auto; margin-right: auto; }

        .cart-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--text);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            transition: color .15s ease;
            position: relative;
        }
        .cart-link:hover { color: var(--primary); }
        .cart-icon {
            width: 18px; height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.3;
        }
        .cart-count {
            position: absolute;
            top: -.25rem; right: -.625rem;
            background: var(--text);
            color: white;
            border-radius: 999px;
            min-width: 16px; height: 16px;
            font-size: 0.625rem;
            line-height: 16px;
            text-align: center;
            font-family: system-ui, sans-serif;
            letter-spacing: 0;
            padding: 0 4px;
        }
        .account-link {
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            color: var(--text-muted);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            transition: color .15s ease;
        }
        .account-link:hover { color: var(--text); }

        /* -------- Language dropdown -------- */
        .lang-menu { position: relative; }
        .lang-menu summary {
            list-style: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .375rem;
            color: var(--text-muted);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            user-select: none;
            transition: color .15s ease;
        }
        .lang-menu summary::-webkit-details-marker,
        .lang-menu summary::marker { display: none; content: none; }
        .lang-menu summary:hover { color: var(--text); }
        .lang-menu .globe {
            width: 14px; height: 14px;
            fill: none; stroke: currentColor; stroke-width: 1.4;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .lang-menu .chevron {
            width: 9px; height: 9px;
            fill: none; stroke: currentColor; stroke-width: 1.6;
            transition: transform .15s ease;
        }
        .lang-menu[open] .chevron { transform: rotate(180deg); }
        .lang-menu-items {
            position: absolute;
            top: calc(100% + .75rem);
            left: 0;
            min-width: 160px;
            background: white;
            border: 1px solid var(--hair);
            padding: .25rem;
            z-index: 60;
        }
        .lang-menu-items a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .625rem .875rem;
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1rem;
            letter-spacing: 0;
            text-transform: none;
            transition: color .15s ease, background-color .12s ease;
        }
        .lang-menu-items a:hover { background: var(--muted); color: var(--text); }
        .lang-menu-items a.active { color: var(--text); font-style: italic; }
        .lang-menu-items .check {
            width: 12px; height: 12px;
            fill: none; stroke: currentColor; stroke-width: 2;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .lang-menu-items a:not(.active) .check { visibility: hidden; }

        /* -------- Main -------- */
        main { min-height: 60vh; }

        /* -------- Toast -------- */
        .toast {
            position: fixed;
            top: 1.25rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--text);
            color: white;
            padding: .75rem 1.5rem;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            z-index: 100;
            animation: toastIn .25s ease-out, toastOut .25s ease-in 3s forwards;
        }
        @keyframes toastIn { from { opacity: 0; transform: translate(-50%, -.5rem); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes toastOut { to { opacity: 0; } }

        /* -------- Footer -------- */
        footer.site {
            border-top: 1px solid var(--hair);
            margin-top: 6rem;
            padding: 4rem 2rem 2rem;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .footer-brand h3 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: 1.75rem;
            margin: 0 0 .5rem;
            letter-spacing: 0.02em;
        }
        .footer-brand p {
            color: var(--text-muted);
            font-size: 0.875rem;
            max-width: 280px;
            margin: 0;
            font-style: italic;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.0625rem;
        }
        .newsletter {
            margin-top: 1.5rem;
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            max-width: 360px;
            border-bottom: 1px solid var(--text);
            padding-bottom: .5rem;
        }
        .newsletter input {
            flex: 1;
            border: 0;
            background: transparent;
            font: inherit;
            font-size: 0.875rem;
            padding: .25rem 0;
            color: var(--text);
        }
        .newsletter input::placeholder { color: var(--text-soft); }
        .newsletter input:focus { outline: none; }
        .newsletter button {
            border: 0;
            background: transparent;
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            cursor: pointer;
            color: var(--text);
            padding: .25rem 0;
        }
        .footer-col h4 {
            margin: 0 0 1rem;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text);
            font-weight: 500;
        }
        .footer-col a {
            display: block;
            padding: .375rem 0;
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.0625rem;
            transition: color .15s ease;
        }
        .footer-col a:hover { color: var(--text); }
        .footer-bottom {
            max-width: 1200px;
            margin: 4rem auto 0;
            padding-top: 1.5rem;
            border-top: 1px solid var(--hair);
            display: flex;
            justify-content: space-between;
            font-size: 0.625rem;
            color: var(--text-soft);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .footer-bottom a { color: var(--text-muted); }

        /* -------- Scroll reveal -------- */
        .reveal {
            opacity: 0;
            transform: translateY(16px);
            transition: opacity .7s ease, transform .7s ease;
        }
        .reveal.in-view { opacity: 1; transform: translateY(0); }

        @media (max-width: 720px) {
            .site-inner { grid-template-columns: 1fr; gap: 1rem; padding: 1rem 1.25rem; }
            .nav-left { order: 2; justify-content: center; }
            .brand { order: 1; }
            .nav-right { order: 3; justify-content: center; }
            .footer-inner { grid-template-columns: 1fr; gap: 2rem; }
            .footer-bottom { flex-direction: column; text-align: center; }
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
    @php
        // Merchant-configurable chrome — falls back to the original
        // hardcoded copy when the merchant hasn't customized.
        $csAnnouncement = $store->announcementBar();
        $csNavMenu = $store->navMenuItems();
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        <div class="announce">
            @if ($csAnnouncement['link'])
                <a href="{{ $csAnnouncement['link'] }}" style="color: inherit;">{{ $csAnnouncement['text'] }}</a>
            @else
                {{ $csAnnouncement['text'] }}
            @endif
        </div>
    @endif

    <header class="site">
        @php
            $customer = auth('customer')->user();
            $currentLocale = app()->getLocale();
            $languages = \App\Http\Middleware\SetLocale::available();
            $cartCount = \App\Services\Cart::forCurrent()->itemCount();
            $supportedCurrencies = $store->supportedDisplayCurrencies();
        @endphp
        <div class="site-inner">
            <div class="nav-left">
                @if (! empty($csNavMenu))
                    @foreach ($csNavMenu as $item)
                        <a href="{{ $item['url'] }}" class="nav-link">{{ $item['label'] }}</a>
                    @endforeach
                @else
                    <a href="/" class="nav-link">{{ __('site.storefront.nav.shop') }}</a>
                @endif
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
            </div>
            <a href="/" class="brand">
                @if ($store->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="{{ $tenant->name }}">
                @endif
                {{ $tenant->name }}
            </a>
            <div class="nav-right">
                @if ($store->showsAccountUi())
                    @if ($customer)
                        <a href="/account" class="account-link">{{ __('site.common.hi_name', ['name' => explode(' ', $customer->name)[0]]) }}</a>
                    @else
                        <a href="/account/login" class="account-link">{{ __('site.common.sign_in') }}</a>
                    @endif
                @endif
                <a href="/cart" class="cart-link" aria-label="{{ __('site.common.cart') }}">
                    <svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 4h2l2.4 12.5a2 2 0 0 0 2 1.5h8.4a2 2 0 0 0 2-1.6L21 8H6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1.4"/>
                        <circle cx="18" cy="20" r="1.4"/>
                    </svg>
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
                <form class="newsletter"
                      data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}"
                      onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                    <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                    <button type="submit">{{ __('site.storefront.footer.subscribe') }} →</button>
                </form>
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
