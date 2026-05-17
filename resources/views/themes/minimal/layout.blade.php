<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title . ' — ' . $tenant->name : $tenant->name }}</title>
    <style>
        :root {
            --primary: {{ $store->primary_color }};
            --secondary: {{ $store->secondary_color }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: {{ $store->font_family }}, Georgia, 'Times New Roman', serif;
            color: var(--secondary);
            background: white;
            line-height: 1.6;
        }
        header {
            padding: 3rem 2rem 2rem;
            text-align: center;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        header img { height: 48px; margin-bottom: 1rem; }
        header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 300;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        header .cart-link {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: var(--secondary);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            text-decoration: none;
            border-bottom: 1px solid var(--secondary);
            padding-bottom: 2px;
        }
        header .lang-switch {
            position: absolute;
            top: 2rem;
            left: 2rem;
            display: inline-flex;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
        }
        header .lang-switch a {
            color: var(--secondary);
            padding: 0 .375rem;
            opacity: .55;
        }
        header .lang-switch a.active { opacity: 1; border-bottom: 1px solid var(--secondary); }
        main { max-width: 720px; margin: 3rem auto; padding: 0 2rem; }
        .toast {
            position: fixed;
            top: 1.25rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--secondary);
            color: white;
            padding: .75rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            z-index: 100;
            animation: fadeIn .25s ease-out, fadeOut .25s ease-in 3s forwards;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translate(-50%, -.5rem); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes fadeOut { to { opacity: 0; } }
        @media (max-width: 640px) {
            header { padding: 2rem 1.25rem 1.5rem; }
            header h1 { font-size: 1.25rem; }
            header .cart-link,
            header .lang-switch { position: static; display: block; margin-top: 1rem; }
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        footer {
            text-align: center;
            padding: 3rem;
            color: #999;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <header>
        @php $currentLocale = app()->getLocale(); @endphp
        <div class="lang-switch">
            <a href="/lang/en" class="@if($currentLocale==='en') active @endif">EN</a>
            <a href="/lang/bg" class="@if($currentLocale==='bg') active @endif">BG</a>
        </div>
        @if ($store->logo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($store->logo_path) }}" alt="{{ $tenant->name }} logo">
        @endif
        <h1><a href="/" style="color: var(--secondary);">{{ $tenant->name }}</a></h1>
        @php $cartCount = \App\Services\Cart::forCurrent()->itemCount(); @endphp
        <a href="/cart" class="cart-link">{{ __('site.common.cart') }}{{ $cartCount > 0 ? " · {$cartCount}" : '' }}</a>
    </header>
    @if (session('cart.flash'))
        <div class="toast">{{ session('cart.flash') }}</div>
    @endif
    <main>
        @yield('content')
    </main>
    <footer>{!! __('site.common.powered_by', ['brand' => 'Ganvo']) !!}</footer>
</body>
</html>
