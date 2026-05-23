@extends('themes.gallery.layout')

@section('content')
    <style>
        /* Gallery grid — asymmetric editorial layout. First product gets
           a hero 2-column span; the rest tile in a regular 3-up grid.
           Goal: products dominate; chrome stays out of the way. */
        .gallery-section { padding: 5rem 0; }
        .gallery-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 2.5rem;
            text-align: center;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 2.5rem 2rem;
        }
        .gallery-card {
            display: block;
            color: inherit;
            grid-column: span 2;
            cursor: pointer;
            transition: transform .35s cubic-bezier(.2,.7,.2,1);
        }
        .gallery-card:hover { transform: translateY(-6px); }
        .gallery-card.feature { grid-column: span 4; grid-row: span 2; }
        .gallery-img {
            position: relative;
            width: 100%;
            background: var(--muted);
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .gallery-card .gallery-img { aspect-ratio: 3 / 4; }
        .gallery-card.feature .gallery-img { aspect-ratio: 1 / 1; }
        .gallery-img img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .8s cubic-bezier(.2,.7,.2,1);
        }
        .gallery-card:hover .gallery-img img { transform: scale(1.04); }
        .gallery-img .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        .gallery-img .pill {
            position: absolute; top: 1rem; left: 1rem;
            background: var(--bg);
            color: var(--text);
            font-size: 0.625rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            padding: .25rem .5rem;
            font-weight: 600;
        }
        .gallery-meta { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
        .gallery-meta h3 {
            margin: 0;
            font-size: 1.0625rem;
            font-weight: 500;
            letter-spacing: -0.005em;
            line-height: 1.3;
        }
        .gallery-card.feature .gallery-meta h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .gallery-meta .price {
            color: var(--text-muted);
            font-size: 0.9375rem;
            font-weight: 500;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .gallery-empty {
            text-align: center;
            padding: 6rem 1rem;
            color: var(--text-soft);
            font-size: 1rem;
        }

        @media (max-width: 960px) {
            .gallery-grid { grid-template-columns: repeat(4, 1fr); }
            .gallery-card { grid-column: span 2; }
            .gallery-card.feature { grid-column: span 4; grid-row: span 1; }
            .gallery-card.feature .gallery-img { aspect-ratio: 16/9; }
        }
        @media (max-width: 640px) {
            .gallery-grid { grid-template-columns: 1fr; }
            .gallery-card, .gallery-card.feature { grid-column: 1 / -1; }
        }
    </style>

    @php $csHero = $store->heroBanner(); @endphp

    @if ($csHero['enabled'] && ($csHero['title'] !== '' || $csHero['subtitle'] !== '' || $csHero['image_path']))
        <section class="custom-hero {{ $csHero['image_path'] ? 'with-image' : '' }}">
            @if ($csHero['image_path'])
                <div class="bg-img" style="background-image: url('{{ \Illuminate\Support\Facades\Storage::url($csHero['image_path']) }}');" aria-hidden="true"></div>
            @endif
            <div class="custom-hero-inner">
                @if ($csHero['title'] !== '')<h2>{{ $csHero['title'] }}</h2>@endif
                @if ($csHero['subtitle'] !== '')<p>{{ $csHero['subtitle'] }}</p>@endif
                @if ($csHero['cta_label'] !== '' && $csHero['cta_url'] !== '')
                    <a href="{{ $csHero['cta_url'] }}" class="cta">{{ $csHero['cta_label'] }}</a>
                @endif
            </div>
        </section>
    @endif

    <section class="gallery-section container" id="shop">
        <p class="gallery-eyebrow">{{ __('site.storefront.shop_all.h2') }}</p>

        @include('storefront.partials.catalog-controls')

        @if ($products->isEmpty())
            <div class="gallery-empty">{{ __('site.storefront.no_products') }}</div>
        @else
            @php
                // On filtered/paginated views, drop the asymmetric "feature
                // card" — it'd inconsistently promote whichever product
                // happens to land first, which is meaningless under
                // search/filter semantics. Plain uniform grid instead.
                $useFeatureLayout = ! (
                    ($filters['q'] ?? null)
                    || ($filters['category'] ?? null)
                    || ($filters['min_price'] ?? null) !== null
                    || ($filters['max_price'] ?? null) !== null
                    || ($filters['in_stock'] ?? false)
                    || (($filters['sort'] ?? 'newest') !== 'newest')
                    || $products->currentPage() > 1
                );
            @endphp
            <div class="gallery-grid">
                @foreach ($products as $i => $product)
                    <a href="/products/{{ $product->slug }}" class="gallery-card {{ ($useFeatureLayout && $i === 0) ? 'feature' : '' }}">
                        <div class="gallery-img">
                            @if ($useFeatureLayout && $i === 0)<span class="pill">{{ __('site.storefront.featured.badge') }}</span>@endif
                            @if ($product->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                            @else
                                <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                            @endif
                        </div>
                        <div class="gallery-meta">
                            <h3>{{ $product->name }}</h3>
                            <div class="price">@money($product->price_cents)</div>
                        </div>
                    </a>
                @endforeach
            </div>

            @include('storefront.partials.pagination')
        @endif
    </section>
@endsection
