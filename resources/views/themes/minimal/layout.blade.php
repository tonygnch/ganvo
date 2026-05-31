<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Lumine hard-codes Marcellus (display serif) + Mulish (body). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Cormorant+Garamond:wght@500;600&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: {{ $store->primary_color ?: '#c0735a' }};
            --display: "Marcellus", serif;
            --body: "Mulish", system-ui, sans-serif;
            --bg: #fbf4ef; --ink: #4a342c; --soft: #f3ddd0; --blush: #f6e3d9;
            --card: #ffffff; --line: #ecd9cd; --muted: #9c8074;

            /* aliases for shared pages + number-anim */
            --primary: var(--accent); --primary-strong: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--card));
            --secondary: var(--blush);
            --text: var(--ink); --text-muted: #7a5e54; --text-soft: var(--muted);
            --border: var(--line); --surface: var(--card);
            --vp-radius: 14px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1240px; margin: 0 auto; padding: 0 40px; }
        .serif { font-family: var(--display); }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        .ph {
            position: relative; background: var(--soft); border-radius: 24px;
            background-image: repeating-linear-gradient(45deg, rgba(74,52,44,.045) 0 11px, transparent 11px 22px);
            display: grid; place-items: center; overflow: hidden;
        }
        .ph span { font-family: var(--body); font-size: 11px; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); background: rgba(255,255,255,.66); padding: 5px 11px; border-radius: 99px; }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-size: 14px; font-weight: 600; padding: 15px 32px; border: 1.5px solid var(--accent); background: var(--accent); color: #fff; border-radius: 99px; transition: .2s; }
        .btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover { background: var(--ink); color: var(--bg); filter: none; }
        .btn.block { width: 100%; }

        .marquee { background: var(--accent); color: #fff; text-align: center; font-size: 12px; letter-spacing: .06em; padding: 9px; }
        .marquee a { color: inherit; text-decoration: underline; }

        header.site { position: sticky; top: 0; z-index: 50; background: rgba(251,244,239,.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
        .nav { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; height: 78px; }
        .nav .left, .nav .right { display: flex; gap: 26px; align-items: center; font-size: 14px; }
        .nav .right { justify-content: flex-end; }
        .nav a.lk { color: var(--ink); }
        .nav a.lk:hover { color: var(--accent); }
        .logo { font-family: var(--display); font-size: 28px; letter-spacing: .18em; text-transform: uppercase; text-align: center; color: var(--ink); white-space: nowrap; }
        .logo img { height: 34px; width: auto; display: inline-block; }
        .bag .n { background: var(--accent); color: #fff; width: 20px; height: 20px; border-radius: 50%; font-size: 11px; display: inline-grid; place-items: center; margin-left: 5px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--ink); }

        /* dropdowns */
        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); font-size: 14px; }
        .menu summary::-webkit-details-marker { display: none; }
        .menu summary:hover, .menu[open] summary { color: var(--accent); }
        .menu .chev { width: 10px; height: 10px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); left: 0; min-width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 8px; z-index: 60; box-shadow: 0 20px 50px -20px rgba(120,70,50,.3); }
        .menu.right-align .menu-items { left: auto; right: 0; }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 13px; border-radius: 10px; color: var(--ink); font-size: 13px; }
        .menu-items a:hover { background: var(--blush); color: var(--accent); }
        .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: 26px; font-size: 12px; }
        .menu-items a.active { color: var(--accent); }

        /* reveal */
        .rv { opacity: 0; transform: translateY(28px); }
        .rv.rv-in { opacity: 1; transform: none; transition: opacity .85s ease, transform 1s cubic-bezier(.16,.84,.3,1); }
        @media (prefers-reduced-motion: reduce) { .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; } }

        /* shared section head */
        .sec-head { text-align: center; margin: 70px 0 36px; }
        .sec-head .k { letter-spacing: .18em; text-transform: uppercase; font-size: 12px; color: var(--accent); font-weight: 700; }
        .sec-head h2 { font-family: var(--display); font-size: clamp(32px,4vw,50px); margin-top: 10px; }

        /* product grid + card */
        .pgrid { display: grid; grid-template-columns: repeat(4,1fr); gap: 24px; }
        .pcard { background: var(--card); border-radius: 26px; padding: 18px; cursor: pointer; transition: .25s; text-align: center; display: block; color: inherit; }
        .pcard:hover { transform: translateY(-5px); box-shadow: 0 24px 50px -26px rgba(120,70,50,.35); }
        .pcard .img { height: 230px; background: var(--blush); border-radius: 18px; margin-bottom: 16px; overflow: hidden; }
        .pcard .img img { width: 100%; height: 100%; object-fit: cover; }
        .pcard .cat { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
        .pcard .nm { font-family: var(--display); font-size: 19px; margin: 5px 0 2px; }
        .pcard .pr { font-size: 15px; color: var(--accent); font-weight: 600; }
        .pcard .add { margin-top: 14px; width: 100%; border: 1.5px solid var(--line); background: none; border-radius: 99px; padding: 10px; font-size: 13px; font-weight: 600; color: var(--ink); transition: .2s; }
        .pcard:hover .add { border-color: var(--accent); color: var(--accent); }

        /* toast */
        .toast { position: fixed; top: 18px; right: 18px; background: var(--accent); color: #fff; padding: 13px 18px; border-radius: 99px; font-size: 13px; font-weight: 600; z-index: 100; animation: tIn .25s ease, tOut .25s ease 3s forwards; }
        @keyframes tIn { from { opacity: 0; transform: translateY(-8px); } } @keyframes tOut { to { opacity: 0; transform: translateY(-8px); } }

        /* footer */
        footer.site { background: var(--blush); margin-top: 30px; padding: 64px 0 36px; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { text-align: left; font-size: 24px; }
        .fcol h4 { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: #6b4f45; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); flex-wrap: wrap; gap: 12px; }
        .fnews { display: flex; gap: 8px; margin-top: 14px; max-width: 320px; }
        .fnews input { flex: 1; border: 1.5px solid var(--line); border-radius: 99px; background: var(--bg); padding: 11px 16px; font-family: inherit; font-size: 13px; }
        .fnews button { background: var(--accent); color: #fff; border: 0; border-radius: 99px; padding: 0 18px; font-weight: 600; font-size: 13px; cursor: pointer; }

        @media (max-width: 1000px) { .pgrid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 720px) {
            .wrap { padding: 0 20px; }
            .nav .left { display: none; } .nav { grid-template-columns: auto 1fr auto; } .menu-toggle { display: block; }
            .logo { text-align: left; }
            .pgrid { grid-template-columns: 1fr 1fr; gap: 14px; } .pcard .img { height: 160px; }
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
        <div class="marquee">@if ($csAnnouncement['link'])<a href="{{ $csAnnouncement['link'] }}">{{ $csAnnouncement['text'] }}</a>@else{{ $csAnnouncement['text'] }}@endif</div>
    @endif

    <header class="site">
        <div class="wrap">
            <div class="nav">
                <button class="menu-toggle" aria-label="Menu">☰</button>
                <div class="left">
                    @if (! empty($csNavMenu))
                        @foreach ($csNavMenu as $item)
                            @if (! empty($item['children']))
                                <details class="menu">
                                    <summary>{{ $item['label'] }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                                    <div class="menu-items">
                                        @if ($item['url'])<a href="{{ $item['url'] }}">{{ __('site.storefront.featured.browse_all') }}</a>@endif
                                        @foreach ($item['children'] as $child)<a href="{{ $child['url'] }}" data-depth="{{ $child['depth'] ?? 0 }}">{{ $child['label'] }}</a>@endforeach
                                    </div>
                                </details>
                            @else
                                <a class="lk" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            @endif
                        @endforeach
                    @else
                        <a class="lk" href="/">{{ __('site.storefront.nav.shop') }}</a>
                    @endif
                </div>
                <a class="logo" href="/">@if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{{ $tenant->name }}@endif</a>
                <div class="right">
                    <details class="menu right-align">
                        <summary>{{ strtoupper($currentLocale) }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                        <div class="menu-items">@foreach ($languages as $code => $name)<a href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">{{ $name }}</a>@endforeach</div>
                    </details>
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu right-align">
                            <summary>{{ $displayCurrency }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items">@foreach ($supportedCurrencies as $code)<a href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif">{{ \App\Services\Money::symbol($code) }} · {{ $code }}</a>@endforeach</div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        <a class="lk" href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                    @endif
                    <a class="lk bag" href="/cart">{{ __('site.common.cart') }}<span class="n">{{ $cartCount }}</span></a>
                </div>
            </div>
        </div>
    </header>

    @if (session('cart.flash'))<div class="toast">{{ session('cart.flash') }}</div>@endif

    @yield('content')

    <footer class="site">
        <div class="wrap">
            <div class="fgrid">
                <div>
                    <div class="logo">{{ $tenant->name }}</div>
                    <p style="color: var(--muted); max-width: 30ch; margin-top: 14px; font-size: 14px;">{{ __('site.storefront.footer.tagline') }}</p>
                    <form class="fnews" data-subscribed-label="{{ __('site.storefront.footer.subscribed') }}" onsubmit="event.preventDefault(); this.querySelector('input').value=''; this.querySelector('button').textContent=this.dataset.subscribedLabel;">
                        <input type="email" placeholder="{{ __('site.storefront.footer.newsletter_placeholder') }}" required>
                        <button type="submit">{{ __('site.storefront.footer.subscribe') }}</button>
                    </form>
                </div>
                <div class="fcol"><h4>{{ __('site.storefront.footer.col_shop') }}</h4><a href="/">{{ __('site.storefront.footer.all_products') }}</a><a href="/#featured">{{ __('site.storefront.nav.featured') }}</a><a href="/cart">{{ __('site.common.cart') }}</a></div>
                <div class="fcol"><h4>{{ __('site.storefront.footer.col_help') }}</h4><a href="#">{{ __('site.storefront.footer.shipping') }}</a><a href="#">{{ __('site.storefront.footer.returns') }}</a><a href="#">{{ __('site.storefront.footer.contact') }}</a></div>
                <div class="fcol"><h4>{{ __('site.lang.switch') }}</h4>@foreach ($languages as $code => $name)<a href="/lang/{{ $code }}">{{ $name }}</a>@endforeach</div>
            </div>
            <div class="fbot">
                <span>© {{ date('Y') }} {{ $tenant->name }}. {{ __('site.common.all_rights') }}</span>
                <span>{!! __('site.common.powered_by', ['brand' => '<a href="http://' . config('ganvo.central_domain') . ':8000" target="_blank" rel="noopener" style="color:var(--accent)">Ganvo</a>']) !!}</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            if (! ('IntersectionObserver' in window)) { document.querySelectorAll('.rv').forEach(function (el) { el.classList.add('rv-in'); }); return; }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e, i) { if (e.isIntersecting) { e.target.style.transitionDelay = Math.min(i * 55, 500) + 'ms'; e.target.classList.add('rv-in'); io.unobserve(e.target); } });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
