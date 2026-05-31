<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Volt hard-codes its typography: Archivo (display) + Space Grotesk
         (body) + Space Mono (technical accents). Merchant font_family is
         intentionally ignored for this theme. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Space+Mono:wght@400;700&family=Archivo:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Merchant's primary_color drives the neon accent; falls back to
               Volt's signature lime. Everything else is the Volt dark palette. */
            --accent: {{ $store->primary_color ?: '#c6ff3d' }};
            --display: "Space Grotesk", sans-serif;
            --archivo: "Archivo", sans-serif;
            --mono: "Space Mono", monospace;
            --bg: #0a0b0e;
            --surface: #13151c;
            --surface2: #1a1d26;
            --line: #23262f;
            --txt: #f2f4f8;
            --muted: #8b909c;
            --faint: #5b606c;

            /* Aliases so shared storefront pages (cart/checkout/order/auth/
               account) + the shared number-anim engine render correctly. */
            --primary: var(--accent);
            --primary-strong: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 16%, var(--surface));
            --secondary: var(--surface);
            --text: var(--txt);
            --text-muted: var(--muted);
            --text-soft: var(--faint);
            --border: var(--line);
            --vp-radius: 6px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body {
            background: var(--bg);
            color: var(--txt);
            font-family: var(--display);
            line-height: 1.55;
            font-size: 16px;
            min-height: 100vh;
        }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1280px; margin: 0 auto; padding: 0 36px; }
        .accent { color: var(--accent); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

        .ph {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--line);
            background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.025) 0 11px, transparent 11px 22px);
            display: grid; place-items: center; overflow: hidden; border-radius: 6px;
        }
        .ph span {
            font-family: var(--mono); font-size: 11px; letter-spacing: .06em; color: var(--faint);
            background: rgba(10,11,14,.6); padding: 5px 10px; border-radius: 4px;
        }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 9px;
            font-size: 14px; font-weight: 600; padding: 15px 28px;
            border: 1px solid var(--accent); background: var(--accent); color: #0a0b0e;
            border-radius: 6px; transition: .15s; letter-spacing: .01em;
        }
        .btn:hover { box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent); }
        .btn.ghost { background: transparent; color: var(--txt); border-color: var(--line); }
        .btn.ghost:hover { border-color: var(--txt); box-shadow: none; }
        .btn.block { width: 100%; }
        .tag { font-family: var(--mono); font-size: 11px; letter-spacing: .08em; color: var(--accent); }

        /* marquee announcement */
        .marquee {
            background: var(--accent); color: #0a0b0e; font-family: var(--mono);
            font-size: 11px; letter-spacing: .06em; padding: 8px 0; font-weight: 700;
            overflow: hidden; white-space: nowrap;
        }
        .marquee .track { display: inline-block; animation: scrollx 22s linear infinite; }
        .marquee .track span { padding: 0 26px; }
        .marquee a { color: inherit; }
        @keyframes scrollx { from { transform: translateX(0); } to { transform: translateX(-50%); } }

        /* header */
        header.site {
            position: sticky; top: 0; z-index: 50;
            background: rgba(10,11,14,.84); backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--line);
        }
        .nav { display: flex; align-items: center; gap: 36px; height: 70px; }
        .logo {
            font-family: var(--archivo); font-weight: 800; font-size: 23px; letter-spacing: -.02em;
            color: var(--txt); text-transform: uppercase; white-space: nowrap;
        }
        .logo b { color: var(--accent); }
        .logo img { height: 30px; width: auto; }
        .nav .links { display: flex; gap: 26px; font-size: 14px; color: var(--muted); }
        .nav .links a:hover { color: var(--txt); }
        .nav .right { margin-left: auto; display: flex; gap: 20px; align-items: center; font-size: 14px; color: var(--muted); }
        .nav .right a:hover { color: var(--txt); }
        .bag { display: inline-flex; align-items: center; gap: 7px; color: var(--txt); }
        .bag .n { background: var(--accent); color: #0a0b0e; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 4px; font-size: 11px; display: grid; place-items: center; font-weight: 700; font-family: var(--mono); }
        .menu-toggle { display: none; background: none; border: none; color: var(--txt); font-size: 22px; }

        /* nav dropdowns (grouped nav) */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 14px; }
        .menu summary::-webkit-details-marker { display: none; }
        .menu summary:hover, .menu[open] summary { color: var(--txt); }
        .menu .chev { width: 10px; height: 10px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items {
            position: absolute; top: calc(100% + 12px); left: 0; min-width: 200px;
            background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 6px; z-index: 60;
            box-shadow: 0 16px 40px -12px rgba(0,0,0,.6);
        }
        .menu.right-align .menu-items { left: auto; right: 0; }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 12px; border-radius: 5px; color: var(--muted); font-size: 13px; }
        .menu-items a:hover { background: var(--surface2); color: var(--txt); }
        .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: 26px; font-size: 12px; }
        .menu-items a.active { color: var(--accent); }

        /* reveal */
        .rv { opacity: 0; transform: translateY(20px); }
        .rv.rv-in { opacity: 1; transform: none; transition: opacity .5s ease, transform .62s cubic-bezier(.2,.85,.2,1); }
        @media (prefers-reduced-motion: reduce) {
            .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; }
            .marquee .track { animation: none !important; }
        }

        /* shared section head */
        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; margin: 70px 0 28px; gap: 1rem; flex-wrap: wrap; }
        .sec-head h2 { font-family: var(--archivo); font-weight: 800; font-size: clamp(26px,3.4vw,40px); letter-spacing: -.02em; }
        .sec-head a { font-family: var(--mono); font-size: 12px; color: var(--muted); }
        .sec-head a:hover { color: var(--accent); }

        /* product grid + card (used across index/category/collection/related) */
        .pgrid { display: grid; grid-template-columns: repeat(4,1fr); gap: 18px; }
        .pcard { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 16px; cursor: pointer; transition: .18s; display: block; color: inherit; }
        .pcard:hover { border-color: #3a3f4d; transform: translateY(-3px); }
        .pcard .img { height: 200px; background: var(--surface2); border-radius: 8px; margin-bottom: 16px; position: relative; overflow: hidden; }
        .pcard .img img { width: 100%; height: 100%; object-fit: cover; }
        .pcard .badge { position: absolute; top: 10px; left: 10px; background: var(--accent); color: #0a0b0e; font-family: var(--mono); font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; z-index: 2; }
        .pcard .cat { font-family: var(--mono); font-size: 11px; color: var(--faint); }
        .pcard .nm { font-size: 16px; font-weight: 600; margin: 5px 0 10px; }
        .pcard .foot { display: flex; align-items: center; justify-content: space-between; }
        .pcard .pr { font-family: var(--mono); font-size: 16px; color: var(--accent); }
        .pcard .add { width: 34px; height: 34px; border: 1px solid var(--line); background: none; color: var(--txt); border-radius: 7px; font-size: 18px; display: grid; place-items: center; }
        .pcard:hover .add { border-color: var(--accent); color: var(--accent); }

        /* toast */
        .toast {
            position: fixed; top: 18px; right: 18px; background: var(--accent); color: #0a0b0e;
            font-family: var(--mono); font-size: 12px; font-weight: 700; padding: 12px 16px; border-radius: 6px;
            z-index: 100; animation: tIn .25s ease, tOut .25s ease 3s forwards;
        }
        @keyframes tIn { from { opacity: 0; transform: translateY(-8px); } } @keyframes tOut { to { opacity: 0; transform: translateY(-8px); } }

        /* footer */
        footer.site { border-top: 1px solid var(--line); margin-top: 30px; padding: 60px 0 34px; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fcol h4 { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; color: var(--faint); margin-bottom: 16px; text-transform: uppercase; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: var(--muted); }
        .fcol a:hover { color: var(--txt); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-family: var(--mono); font-size: 12px; color: var(--faint); flex-wrap: wrap; gap: 12px; }
        .fnews { display: flex; gap: 8px; margin-top: 14px; max-width: 320px; }
        .fnews input { flex: 1; background: var(--bg); border: 1px solid var(--line); border-radius: 7px; padding: 11px 13px; color: var(--txt); font-family: var(--mono); font-size: 12px; }
        .fnews button { background: var(--accent); color: #0a0b0e; border: 0; border-radius: 7px; padding: 0 16px; font-weight: 700; font-size: 12px; font-family: var(--mono); cursor: pointer; }

        @media (max-width: 1000px) { .pgrid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 720px) {
            .wrap { padding: 0 18px; }
            .nav .links { display: none; }
            .nav .right a:not(.bag) { display: none; }
            .pgrid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
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
        $logoUrl = $store->logo_path ? \Illuminate\Support\Facades\Storage::url($store->logo_path) : null;
    @endphp

    @if ($csAnnouncement['enabled'] && $csAnnouncement['text'] !== '')
        <div class="marquee">
            <div class="track">
                @for ($i = 0; $i < 6; $i++)
                    <span>@if ($csAnnouncement['link'])<a href="{{ $csAnnouncement['link'] }}">{{ $csAnnouncement['text'] }}</a>@else{{ $csAnnouncement['text'] }}@endif</span>
                @endfor
            </div>
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
                                <details class="menu">
                                    <summary>{{ $item['label'] }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                                    <div class="menu-items">
                                        @if ($item['url'])<a href="{{ $item['url'] }}">{{ __('site.storefront.featured.browse_all') }}</a>@endif
                                        @foreach ($item['children'] as $child)
                                            <a href="{{ $child['url'] }}" data-depth="{{ $child['depth'] ?? 0 }}">{{ $child['label'] }}</a>
                                        @endforeach
                                    </div>
                                </details>
                            @else
                                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @else
                        <a href="/">{{ __('site.storefront.nav.shop') }}</a>
                    @endif
                </div>
                <div class="right">
                    <details class="menu right-align">
                        <summary>{{ strtoupper($currentLocale) }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                        <div class="menu-items">
                            @foreach ($languages as $code => $name)
                                <a href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">{{ $name }}</a>
                            @endforeach
                        </div>
                    </details>
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu right-align">
                            <summary>{{ $displayCurrency }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items">
                                @foreach ($supportedCurrencies as $code)
                                    <a href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif">{{ \App\Services\Money::symbol($code) }} · {{ $code }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                    @endif
                    <a class="bag" href="/cart">{{ __('site.common.cart') }} <span class="n">{{ $cartCount }}</span></a>
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
                    <p style="color: var(--muted); max-width: 30ch; margin-top: 14px; font-size: 14px;">{{ __('site.storefront.footer.tagline') }}</p>
                    <form class="fnews" data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}"
                          onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                        <button type="submit">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
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
                <span>© {{ date('Y') }} {{ strtoupper($tenant->name) }}. {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener" style="color:var(--accent)">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            if (! ('IntersectionObserver' in window)) {
                document.querySelectorAll('.rv').forEach(function (el) { el.classList.add('rv-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e, i) {
                    if (e.isIntersecting) { e.target.style.transitionDelay = Math.min(i * 45, 400) + 'ms'; e.target.classList.add('rv-in'); io.unobserve(e.target); }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
