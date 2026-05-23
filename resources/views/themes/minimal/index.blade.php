@extends('themes.minimal.layout')

@section('content')
    @php
        // Only show marketing chrome (hero + quote band) on the unfiltered
        // landing — once shoppers search/filter/paginate, jump them
        // straight to results.
        $isFiltered = ($filters['q'] ?? null)
            || ($filters['category'] ?? null)
            || ($filters['min_price'] ?? null) !== null
            || ($filters['max_price'] ?? null) !== null
            || ($filters['in_stock'] ?? false)
            || (($filters['sort'] ?? 'newest') !== 'newest')
            || $products->currentPage() > 1;
    @endphp
    <style>
        /* -------- Editorial hero -------- */
        .hero {
            padding: 6rem 2rem 5rem;
            text-align: center;
            max-width: 880px;
            margin: 0 auto;
        }
        .hero .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 1.5rem;
        }
        .hero h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 1.05;
            letter-spacing: -0.01em;
            margin: 0 0 1.5rem;
            color: var(--text);
        }
        .hero h1 em {
            font-style: italic;
            color: var(--primary);
        }
        .hero .sub {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.25rem;
            line-height: 1.5;
            color: var(--text-muted);
            font-style: italic;
            max-width: 560px;
            margin: 0 auto 2rem;
        }
        .hero .cta {
            display: inline-block;
            padding: .875rem 2rem;
            border: 1px solid var(--text);
            color: var(--text);
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            transition: background-color .2s ease, color .2s ease;
        }
        .hero .cta:hover { background: var(--text); color: white; }

        /* -------- Section heading -------- */
        .section { padding: 4rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-head {
            text-align: center;
            margin-bottom: 4rem;
        }
        .section-head .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .75rem;
        }
        .section-head h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: clamp(1.875rem, 3vw, 2.5rem);
            margin: 0;
            letter-spacing: -0.01em;
        }
        .section-head .hairline {
            width: 40px;
            height: 1px;
            background: var(--text);
            margin: 1.25rem auto 0;
        }

        /* -------- Product grid -------- */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 3rem 2rem;
        }
        .card { display: block; color: inherit; }
        .card-image {
            position: relative;
            aspect-ratio: 4 / 5;
            background: var(--muted);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .card-image img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .7s cubic-bezier(.2,.7,.2,1), opacity .3s ease;
        }
        .card:hover .card-image img { transform: scale(1.04); }
        .card-image .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-family: system-ui, sans-serif;
        }
        .card-body { text-align: center; padding: 0 .5rem; }
        .card-body h3 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: 1.25rem;
            margin: 0 0 .375rem;
            letter-spacing: 0.01em;
            line-height: 1.3;
            transition: color .2s ease;
        }
        .card:hover .card-body h3 { color: var(--primary); }
        .card-body .price {
            font-size: 0.8125rem;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }

        .empty {
            text-align: center;
            padding: 6rem 0;
            color: var(--text-soft);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.25rem;
        }

        /* -------- Editorial quote band -------- */
        .quote-band {
            padding: 6rem 2rem;
            text-align: center;
            border-top: 1px solid var(--hair);
            border-bottom: 1px solid var(--hair);
            background: var(--muted);
            margin-top: 4rem;
        }
        .quote-band blockquote {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-weight: 400;
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            line-height: 1.4;
            max-width: 760px;
            margin: 0 auto 1.5rem;
            color: var(--text);
        }
        .quote-band cite {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-style: normal;
        }

        @media (max-width: 720px) {
            .hero { padding: 3.5rem 1.25rem 3rem; }
            .section { padding: 2.5rem 1.25rem; }
            .section-head { margin-bottom: 2.5rem; }
            .grid { grid-template-columns: repeat(2, 1fr); gap: 2rem 1rem; }
        }

        /* -------- Merchant-configurable hero banner (sits above the editorial hero). -------- */
        .custom-hero {
            position: relative;
            padding: 5rem 1.5rem;
            text-align: center;
            color: var(--text);
            border-bottom: 1px solid var(--hair);
            overflow: hidden;
        }
        .custom-hero.with-image { color: white; border-bottom: 0; }
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
        .custom-hero h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: clamp(2rem, 4.5vw, 3.25rem);
            letter-spacing: -0.01em;
            margin: 0 0 .75rem;
            line-height: 1.05;
        }
        .custom-hero p {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: clamp(1rem, 1.8vw, 1.25rem);
            font-style: italic;
            margin: 0 0 1.75rem;
            opacity: .92;
        }
        .custom-hero .cta {
            display: inline-block;
            padding: .75rem 1.75rem;
            border: 1px solid currentColor;
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: inherit;
            transition: background-color .2s ease, color .2s ease;
        }
        .custom-hero .cta:hover { background: var(--text); color: var(--surface); }
        .custom-hero.with-image .cta:hover { background: white; color: var(--text); }
    </style>

    @php $csHero = $store->heroBanner(); @endphp

    @if (! $isFiltered && $csHero['enabled'] && ($csHero['title'] !== '' || $csHero['subtitle'] !== '' || $csHero['image_path']))
        <section class="custom-hero {{ $csHero['image_path'] ? 'with-image' : '' }} reveal">
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
                    <a href="{{ $csHero['cta_url'] }}" class="cta">{{ $csHero['cta_label'] }}</a>
                @endif
            </div>
        </section>
    @endif

    @if (! $isFiltered)
    <section class="hero reveal">
        <p class="eyebrow">{{ __('site.storefront.hero.eyebrow', ['year' => date('Y')]) }}</p>
        <h1>{!! str_replace('.', '<em>.</em>', __('site.storefront.hero.headline', ['tenant' => $tenant->name])) !!}</h1>
        <p class="sub">{{ __('site.storefront.hero.sub') }}</p>
        <a href="#shop" class="cta">{{ __('site.storefront.hero.cta_primary') }}</a>
    </section>
    @endif

    @if (! $isFiltered && (isset($featuredCollections) ? $featuredCollections->isNotEmpty() : false))
        <section class="section reveal">
            @include('storefront.partials.collection-strips')
        </section>
    @endif

    <section class="section reveal" id="shop">
        <div class="section-head">
            <p class="eyebrow">{{ __('site.storefront.shop_all.eyebrow') }}</p>
            <h2>{{ __('site.storefront.shop_all.h2') }}</h2>
            <div class="hairline"></div>
        </div>

        @include('storefront.partials.catalog-controls')

        @if ($products->isEmpty())
            <p class="empty">{{ __('site.storefront.no_products') }}</p>
        @else
            <div class="grid">
                @foreach ($products as $product)
                    <a href="/products/{{ $product->slug }}" class="card">
                        <div class="card-image">
                            @if ($product->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                            @else
                                <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <h3>{{ $product->name }}</h3>
                            <div class="price">@money($product->price_cents)</div>
                        </div>
                    </a>
                @endforeach
            </div>

            @include('storefront.partials.pagination')
        @endif
    </section>

    @if (! $isFiltered && $products->isNotEmpty())
        <section class="quote-band reveal">
            <blockquote>{{ __('site.storefront.hero.sub') }}</blockquote>
            <cite>— {{ $tenant->name }}</cite>
        </section>
    @endif
@endsection
