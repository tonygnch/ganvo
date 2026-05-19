@extends('themes.default.layout')

@section('content')
    @php
        $featured = $products->take(3);
        $rest = $products->slice(3);
    @endphp

    <style>
        /* -------- Hero -------- */
        .hero {
            position: relative;
            padding: 5rem 1.5rem 6rem;
            overflow: hidden;
            color: white;
            background: linear-gradient(135deg, var(--primary-strong), var(--primary));
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,0.15) 1px, transparent 1.5px);
            background-size: 24px 24px;
            opacity: .35;
            pointer-events: none;
        }
        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 3rem;
            align-items: center;
        }
        .hero-eyebrow {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            padding: .375rem 1rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            letter-spacing: 0.04em;
        }
        .hero h1 {
            font-size: clamp(2.25rem, 4.5vw, 3.5rem);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 1rem;
        }
        .hero p.sub {
            font-size: 1.0625rem;
            max-width: 500px;
            margin: 0 0 1.75rem;
            color: rgba(255,255,255,0.9);
        }
        .hero-ctas { display: flex; gap: .75rem; flex-wrap: wrap; }
        .btn {
            display: inline-block;
            padding: .875rem 1.5rem;
            border-radius: .625rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: transform .12s ease, box-shadow .12s ease, background-color .2s ease;
            cursor: pointer;
            border: 0;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: white;
            color: var(--primary-strong);
            box-shadow: 0 6px 20px -4px rgba(0,0,0,0.2);
        }
        .btn-primary:hover { box-shadow: 0 12px 30px -4px rgba(0,0,0,0.3); }
        .btn-outline {
            background: transparent;
            color: white;
            border: 1.5px solid rgba(255,255,255,0.5);
        }
        .btn-outline:hover { background: rgba(255,255,255,0.1); border-color: white; }

        .hero-art {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-card {
            background: white;
            color: var(--text);
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 24px 60px -10px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 340px;
            transform: rotate(-2deg);
            transition: transform .3s ease;
        }
        .hero-card:hover { transform: rotate(-2deg) translateY(-4px); }
        .hero-card-image {
            aspect-ratio: 1;
            border-radius: .75rem;
            background: linear-gradient(135deg, var(--primary-soft), var(--muted));
            margin-bottom: 1rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .hero-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .hero-card h3 { margin: 0 0 .25rem; font-size: 1.0625rem; }
        .hero-card .price { color: var(--primary-strong); font-weight: 700; font-size: 1.125rem; }
        .hero-card .badge {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-strong);
            padding: .2rem .625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: .75rem;
        }

        @media (max-width: 880px) {
            .hero-inner { grid-template-columns: 1fr; gap: 2rem; }
            .hero-art { order: -1; }
            .hero-card { max-width: 260px; transform: rotate(-2deg) scale(.9); }
        }

        /* -------- Value props strip -------- */
        .value-props {
            background: white;
            border-bottom: 1px solid var(--border);
        }
        .value-props-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .value-prop {
            display: flex;
            align-items: center;
            gap: .75rem;
            justify-content: center;
        }
        .value-prop-icon {
            width: 36px; height: 36px;
            border-radius: .5rem;
            background: var(--primary-soft);
            color: var(--primary-strong);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            flex-shrink: 0;
        }
        .value-prop-text {
            font-size: 0.875rem;
        }
        .value-prop-text strong { display: block; color: var(--text); }
        .value-prop-text span { color: var(--text-muted); font-size: 0.8125rem; }

        @media (max-width: 720px) {
            .value-props-inner { grid-template-columns: 1fr; padding: 1rem; }
            .value-prop { justify-content: flex-start; }
        }

        /* -------- Section headers -------- */
        .section { padding: 4rem 1.5rem; max-width: 1200px; margin: 0 auto; }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .section-eyebrow {
            color: var(--primary-strong);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin: 0 0 .375rem;
        }
        .section h2 {
            margin: 0;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .section-link {
            color: var(--primary-strong);
            font-weight: 600;
            font-size: 0.925rem;
        }
        .section-link:hover { text-decoration: underline; }

        /* -------- Product grid -------- */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .grid.featured-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
        .card {
            background: var(--surface);
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px -10px rgba(15, 23, 42, 0.18);
            border-color: color-mix(in srgb, var(--primary) 35%, var(--border));
        }
        .card-image {
            background: linear-gradient(135deg, var(--muted), var(--surface));
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            overflow: hidden;
            position: relative;
        }
        .card-image img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .4s ease;
        }
        .card:hover .card-image img { transform: scale(1.05); }
        .card-badge {
            position: absolute;
            top: .75rem;
            left: .75rem;
            background: var(--primary);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: .25rem .625rem;
            border-radius: 9999px;
        }
        .card-body { padding: 1rem 1.125rem 1.25rem; display: flex; flex-direction: column; gap: .25rem; flex: 1; }
        .card-body h3 { margin: 0; font-size: 1rem; font-weight: 700; }
        .card-body .desc {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-body .price-row {
            margin-top: auto;
            padding-top: .5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-body .price {
            color: var(--primary-strong);
            font-weight: 700;
            font-size: 1.0625rem;
        }
        .card-body .arrow {
            color: var(--text-soft);
            font-size: 1.25rem;
            transition: transform .2s ease, color .2s ease;
        }
        .card:hover .arrow { color: var(--primary); transform: translateX(2px); }

        .empty {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-soft);
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: 1rem;
        }

        /* -------- Promo strip -------- */
        .promo {
            background: var(--secondary);
            color: white;
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .promo-inner { max-width: 700px; margin: 0 auto; }
        .promo h3 {
            margin: 0 0 .5rem;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .promo p { margin: 0 0 1.5rem; color: rgba(255,255,255,0.8); font-size: 1rem; }
        .promo .btn-primary {
            background: var(--primary);
            color: white;
        }

        /* -------- Custom hero banner (merchant-configurable) -------- */
        .custom-hero {
            position: relative;
            padding: 5rem 1.5rem;
            text-align: center;
            color: var(--text);
            overflow: hidden;
            background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 12%, white), color-mix(in srgb, var(--secondary) 10%, white));
        }
        .custom-hero.with-image { color: white; }
        .custom-hero .bg-img {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
        }
        .custom-hero .bg-img::after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.25) 0%, rgba(0,0,0,.55) 100%);
        }
        .custom-hero-inner { position: relative; max-width: 800px; margin: 0 auto; z-index: 1; }
        .custom-hero h2 { font-size: clamp(2rem, 4.5vw, 3.25rem); font-weight: 800; letter-spacing: -0.02em; margin: 0 0 .75rem; }
        .custom-hero p  { font-size: clamp(1rem, 1.8vw, 1.25rem); margin: 0 0 1.75rem; opacity: .9; }
        .custom-hero .btn-primary { background: var(--primary); color: white; }
    </style>

    @php $csHero = $store->heroBanner(); @endphp

    @if ($csHero['enabled'] && ($csHero['title'] !== '' || $csHero['subtitle'] !== '' || $csHero['image_path']))
        <section class="custom-hero {{ $csHero['image_path'] ? 'with-image' : '' }}">
            @if ($csHero['image_path'])
                <div class="bg-img" style="background-image: url('{{ \Illuminate\Support\Facades\Storage::url($csHero['image_path']) }}');" aria-hidden="true"></div>
            @endif
            <div class="custom-hero-inner">
                @if ($csHero['title'] !== '')
                    <h2>{{ $csHero['title'] }}</h2>
                @endif
                @if ($csHero['subtitle'] !== '')
                    <p>{{ $csHero['subtitle'] }}</p>
                @endif
                @if ($csHero['cta_label'] !== '' && $csHero['cta_url'] !== '')
                    <a href="{{ $csHero['cta_url'] }}" class="btn btn-primary btn-lg">{{ $csHero['cta_label'] }}</a>
                @endif
            </div>
        </section>
    @endif

    <section class="hero">
        <div class="hero-inner">
            <div>
                <span class="hero-eyebrow">{{ __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</span>
                <h1>{{ __('site.storefront.hero.headline', ['tenant' => $tenant->name]) }}</h1>
                <p class="sub">{{ __('site.storefront.hero.sub') }}</p>
                <div class="hero-ctas">
                    <a href="#shop" class="btn btn-primary">{{ __('site.storefront.hero.cta_primary') }}</a>
                    <a href="#featured" class="btn btn-outline">{{ __('site.storefront.hero.cta_secondary') }}</a>
                </div>
            </div>
            <div class="hero-art" aria-hidden="true">
                @if ($featured->first())
                    @php $fp = $featured->first(); @endphp
                    <div class="hero-card">
                        <div class="hero-card-image">
                            @if ($fp->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($fp->image_path) }}" alt="">
                            @else
                                {{ __('site.storefront.featured.badge') }}
                            @endif
                        </div>
                        <span class="badge">{{ __('site.storefront.featured.badge') }}</span>
                        <h3>{{ $fp->name }}</h3>
                        <div class="price">@money($fp->price_cents)</div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="value-props">
        <div class="value-props-inner">
            <div class="value-prop">
                <div class="value-prop-icon">⌁</div>
                <div class="value-prop-text"><strong>{{ __('site.storefront.value_props.shipping_title') }}</strong><span>{{ __('site.storefront.value_props.shipping_sub') }}</span></div>
            </div>
            <div class="value-prop">
                <div class="value-prop-icon">⟲</div>
                <div class="value-prop-text"><strong>{{ __('site.storefront.value_props.returns_title') }}</strong><span>{{ __('site.storefront.value_props.returns_sub') }}</span></div>
            </div>
            <div class="value-prop">
                <div class="value-prop-icon">⚡</div>
                <div class="value-prop-text"><strong>{{ __('site.storefront.value_props.checkout_title') }}</strong><span>{{ __('site.storefront.value_props.checkout_sub') }}</span></div>
            </div>
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="section reveal" id="featured">
            <div class="section-head">
                <div>
                    <p class="section-eyebrow">{{ __('site.storefront.featured.eyebrow') }}</p>
                    <h2>{{ __('site.storefront.featured.h2') }}</h2>
                </div>
                <a href="#shop" class="section-link">{{ __('site.storefront.featured.browse_all') }}</a>
            </div>
            <div class="grid featured-grid">
                @foreach ($featured as $product)
                    <a href="/products/{{ $product->slug }}" class="card">
                        <div class="card-image">
                            @if ($product->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                            @else
                                {{ __('site.storefront.product.no_image') }}
                            @endif
                            <span class="card-badge">{{ __('site.storefront.featured.badge') }}</span>
                        </div>
                        <div class="card-body">
                            <h3>{{ $product->name }}</h3>
                            @if ($product->description)
                                <p class="desc">{{ $product->description }}</p>
                            @endif
                            <div class="price-row">
                                <span class="price">@money($product->price_cents)</span>
                                <span class="arrow">→</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section reveal" id="shop">
        <div class="section-head">
            <div>
                <p class="section-eyebrow">{{ __('site.storefront.shop_all.eyebrow') }}</p>
                <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            </div>
        </div>
        @if ($products->isEmpty())
            <div class="empty">
                <p>{{ __('site.storefront.no_products') }}</p>
            </div>
        @else
            <div class="grid">
                @foreach ($products as $product)
                    <a href="/products/{{ $product->slug }}" class="card">
                        <div class="card-image">
                            @if ($product->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                            @else
                                {{ __('site.storefront.product.no_image') }}
                            @endif
                        </div>
                        <div class="card-body">
                            <h3>{{ $product->name }}</h3>
                            <div class="price-row">
                                <span class="price">@money($product->price_cents)</span>
                                <span class="arrow">→</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="promo reveal">
        <div class="promo-inner">
            <h3>{{ __('site.storefront.promo.h2_prefix', ['tenant' => $tenant->name]) }}</h3>
            <p>{{ __('site.storefront.promo.p') }}</p>
            <a href="#" class="btn btn-primary" onclick="document.querySelector('footer .newsletter input').focus(); event.preventDefault();">{{ __('site.storefront.promo.btn') }}</a>
        </div>
    </section>
@endsection
