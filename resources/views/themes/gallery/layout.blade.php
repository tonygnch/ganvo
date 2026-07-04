<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>

    {{-- Terra hard-codes Bricolage Grotesque (display) + Hanken Grotesk (body). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: {{ $store->primary_color ?: '#b06a4a' }};
            --display: "Bricolage Grotesque", sans-serif;
            --body: "Hanken Grotesk", system-ui, sans-serif;
            --bg: #f3efe7; --ink: #34302a; --soft: #e6ded0; --soft2: #ded5c4;
            --card: #faf7f1; --line: #ddd4c4; --muted: #8c8475;

            --primary: var(--accent); --primary-strong: var(--accent);
            --primary-soft: color-mix(in srgb, var(--accent) 14%, var(--card));
            --secondary: var(--soft);
            --text: var(--ink); --text-muted: var(--muted); --text-soft: var(--muted);
            --border: var(--line); --surface: var(--card);
            --vp-radius: 8px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        body { background: var(--bg); color: var(--ink); font-family: var(--body); line-height: 1.6; font-size: 16px; min-height: 100vh; }
        img { display: block; max-width: 100%; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; }
        .wrap { max-width: 1260px; margin: 0 auto; padding: 0 40px; }
        :focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

        .ph { position: relative; background: var(--soft); border-radius: 14px; background-image: repeating-linear-gradient(45deg, rgba(52,48,42,.04) 0 11px, transparent 11px 22px); display: grid; place-items: center; overflow: hidden; }
        .ph span { font-family: var(--body); font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); background: rgba(250,247,241,.7); padding: 5px 11px; border-radius: 6px; }
        .ph img { width: 100%; height: 100%; object-fit: cover; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; font-size: 14px; font-weight: 600; padding: 15px 30px; border: 1px solid var(--accent); background: var(--accent); color: #faf7f1; border-radius: 8px; transition: .2s; }
        .btn:hover { filter: brightness(1.05); }
        .btn.outline { background: transparent; color: var(--ink); border-color: var(--ink); }
        .btn.outline:hover { background: var(--ink); color: var(--bg); filter: none; }
        .btn.block { width: 100%; }

        .marquee { background: var(--ink); color: var(--bg); text-align: center; font-size: 12px; letter-spacing: .04em; padding: 9px; }
        .marquee a { color: inherit; text-decoration: underline; }

        header.site { position: sticky; top: 0; z-index: 50; background: rgba(243,239,231,.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav { display: flex; align-items: center; gap: 34px; height: 76px; }
        .logo { font-family: var(--display); font-weight: 700; font-size: 25px; letter-spacing: -.02em; color: var(--ink); white-space: nowrap; }
        .logo img { height: 32px; width: auto; }
        .nav .links { display: flex; gap: 26px; font-size: 14.5px; }
        .nav .links a:hover { color: var(--accent); }
        .nav .right { margin-left: auto; display: flex; gap: 22px; align-items: center; font-size: 14.5px; }
        .nav .right a:hover { color: var(--accent); }
        .bag .n { background: var(--accent); color: #faf7f1; width: 19px; height: 19px; border-radius: 50%; font-size: 11px; display: inline-grid; place-items: center; margin-left: 5px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--ink); }

        .menu { position: relative; }
        .menu summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; color: var(--ink); font-size: 14.5px; }
        .menu summary::-webkit-details-marker { display: none; }
        .menu summary:hover, .menu[open] summary { color: var(--accent); }
        .menu .chev { width: 10px; height: 10px; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .menu[open] .chev { transform: rotate(180deg); }
        .menu-items { position: absolute; top: calc(100% + 12px); left: 0; min-width: 200px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 8px; z-index: 60; box-shadow: 0 20px 50px -20px rgba(52,48,42,.3); }
        .menu.right-align .menu-items { left: auto; right: 0; }
        .menu-items a { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 13px; border-radius: 8px; color: var(--ink); font-size: 13px; }
        .menu-items a:hover { background: var(--soft); color: var(--accent); }
        .menu-items a[data-depth]:not([data-depth="0"]) { padding-left: 26px; font-size: 12px; }
        .menu-items a.active { color: var(--accent); }

        .rv { opacity: 0; transform: translateY(26px); }
        .rv.rv-in { opacity: 1; transform: none; transition: opacity .9s ease, transform 1.05s cubic-bezier(.19,.7,.16,1); }
        @media (prefers-reduced-motion: reduce) { .rv, .rv.rv-in { opacity: 1 !important; transform: none !important; transition: none !important; } }

        .sec-head { display: flex; align-items: flex-end; justify-content: space-between; margin: 80px 0 28px; gap: 1rem; flex-wrap: wrap; }
        .sec-head h2 { font-family: var(--display); font-weight: 600; font-size: clamp(28px,3.6vw,44px); letter-spacing: -.02em; }
        .sec-head a { font-size: 14px; color: var(--accent); font-weight: 600; }

        .pgrid { display: grid; grid-template-columns: repeat(4,1fr); gap: 22px; }
        .pcard { cursor: pointer; display: block; color: inherit; }
        .pcard .img { height: 260px; background: var(--soft); border-radius: 14px; margin-bottom: 14px; position: relative; transition: .25s; overflow: hidden; }
        .pcard:hover .img { transform: translateY(-4px); }
        .pcard .img img { width: 100%; height: 100%; object-fit: cover; }
        .pcard .badge { position: absolute; top: 12px; left: 12px; background: var(--card); font-size: 11px; padding: 4px 10px; border-radius: 99px; color: var(--accent); font-weight: 600; z-index: 2; }
        .pcard .cat { font-size: 12px; color: var(--muted); }
        .pcard .nm { font-family: var(--display); font-weight: 600; font-size: 18px; margin: 3px 0; }
        .pcard .pr { font-size: 15px; font-weight: 600; }

        .toast { position: fixed; top: 18px; right: 18px; background: var(--ink); color: var(--bg); padding: 13px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; z-index: 100; animation: tIn .25s ease, tOut .25s ease 3s forwards; }
        @keyframes tIn { from { opacity: 0; transform: translateY(-8px); } } @keyframes tOut { to { opacity: 0; transform: translateY(-8px); } }

        footer.site { background: var(--soft); margin-top: 30px; padding: 64px 0 34px; }
        .fgrid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; }
        .fgrid .logo { font-size: 23px; }
        .fcol h4 { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 16px; }
        .fcol a { display: block; font-size: 14px; margin-bottom: 10px; color: #5e574c; }
        .fcol a:hover { color: var(--accent); }
        .fbot { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 22px; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); flex-wrap: wrap; gap: 12px; }
        .fnews { display: flex; gap: 8px; margin-top: 14px; max-width: 320px; }
        .fnews input { flex: 1; border: 1px solid var(--line); border-radius: 8px; background: var(--card); padding: 11px 14px; font-family: inherit; font-size: 13px; }
        .fnews button { background: var(--accent); color: #faf7f1; border: 0; border-radius: 8px; padding: 0 16px; font-weight: 600; font-size: 13px; cursor: pointer; }

        @media (max-width: 1000px) { .pgrid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 720px) {
            .wrap { padding: 0 20px; }
            .nav .links { display: none; } .menu-toggle { display: block; }
            .nav .right a:not(.bag) { display: none; }
            .pgrid { grid-template-columns: 1fr 1fr; gap: 14px; } .pcard .img { height: 180px; }
            .fgrid { grid-template-columns: 1fr 1fr; }
        }
    </style>
    {!! $theme->headExtras() !!}
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
                <a class="logo" href="/">@if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $tenant->name }}">@else{{ $tenant->name }}@endif</a>
                <div class="links">
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
                        <div class="menu-items">@foreach ($languages as $code => $name)<a href="/lang/{{ $code }}" class="@if($currentLocale===$code) active @endif">{{ $name }}</a>@endforeach</div>
                    </details>
                    @if (count($supportedCurrencies) > 1)
                        <details class="menu right-align">
                            <summary>{{ $displayCurrency }} <svg class="chev" viewBox="0 0 12 12"><path d="M3 4.5L6 7.5L9 4.5"/></svg></summary>
                            <div class="menu-items">@foreach ($supportedCurrencies as $code)<a href="/currency/{{ $code }}" class="@if($displayCurrency===$code) active @endif">{{ \App\Services\Money::symbol($code) }} · {{ $code }}</a>@endforeach</div>
                        </details>
                    @endif
                    @if ($store->showsAccountUi())
                        <a href="{{ $customer ? '/account' : '/account/login' }}">{{ $customer ? __('site.common.my_account') : __('site.common.sign_in') }}</a>
                    @endif
                    <a class="bag" href="/cart">{{ __('site.common.cart') }}<span class="n">{{ $cartCount }}</span></a>
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
                entries.forEach(function (e, i) { if (e.isIntersecting) { e.target.style.transitionDelay = Math.min(i * 50, 500) + 'ms'; e.target.classList.add('rv-in'); io.unobserve(e.target); } });
            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.rv').forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
