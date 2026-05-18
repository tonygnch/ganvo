@php
    $title = $product->name;
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .product-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem 5rem;
        }
        .breadcrumb {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin-bottom: 3rem;
            text-align: center;
        }
        .breadcrumb a { color: var(--text-muted); transition: color .15s ease; }
        .breadcrumb a:hover { color: var(--text); }
        .breadcrumb .sep { margin: 0 .75rem; }

        .product {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 5rem;
            align-items: start;
        }

        /* -------- Gallery -------- */
        .gallery {
            position: relative;
            aspect-ratio: 4 / 5;
            background: var(--muted);
            overflow: hidden;
        }
        .gallery img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .gallery .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            font-family: system-ui, sans-serif;
        }

        /* -------- Info -------- */
        .info {
            padding-top: 2rem;
            position: sticky;
            top: 8rem;
        }
        .info .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 1rem;
        }
        .info h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: clamp(2rem, 3.5vw, 2.875rem);
            line-height: 1.1;
            letter-spacing: -0.01em;
            margin: 0 0 1.25rem;
            color: var(--text);
        }
        .info .price {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.5rem;
            font-style: italic;
            color: var(--primary);
            margin: 0 0 .5rem;
        }
        .info .tax {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0 0 2rem;
        }
        .info .desc {
            color: var(--text-muted);
            line-height: 1.8;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.125rem;
            margin: 0 0 2.5rem;
            padding-bottom: 2.5rem;
            border-bottom: 1px solid var(--hair);
        }

        .stock {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .stock .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
        }

        .add-form { display: flex; gap: .75rem; align-items: stretch; }
        .add-btn {
            flex: 1;
            background: var(--text);
            color: white;
            border: 0;
            padding: 1.125rem 1.5rem;
            font-size: 0.7rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background-color .2s ease;
            font-family: system-ui, sans-serif;
        }
        .add-btn:hover { background: var(--primary); }
        .wishlist-btn {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--hair);
            padding: 0 1.25rem;
            cursor: pointer;
            font-size: 1.125rem;
            transition: border-color .2s ease, color .2s ease;
        }
        .wishlist-btn:hover { color: var(--primary); border-color: var(--primary); }

        /* -------- Detail accordion-style perks -------- */
        .perks {
            margin-top: 3rem;
        }
        .perk {
            border-top: 1px solid var(--hair);
            padding: 1.25rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text);
            font-size: 0.75rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }
        .perk:last-child { border-bottom: 1px solid var(--hair); }
        .perk .label { font-weight: 500; }
        .perk .value {
            color: var(--text-muted);
            font-size: 0.8125rem;
            letter-spacing: 0.05em;
            text-transform: none;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
        }

        @media (max-width: 880px) {
            .product { grid-template-columns: 1fr; gap: 2.5rem; }
            .info { position: static; padding-top: 0; }
        }
        @media (max-width: 480px) {
            .product-page { padding: 2rem 1.25rem 3rem; }
            .breadcrumb { margin-bottom: 2rem; text-align: left; }
        }
    </style>

    <div class="product-page">
        <div class="breadcrumb">
            <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
            <span class="sep">/</span>
            <span>{{ $product->name }}</span>
        </div>

        <div class="product">
            <div class="gallery">
                @if ($product->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                @else
                    <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                @endif
            </div>

            <div class="info">
                <p class="eyebrow">{{ $tenant->name }}</p>
                <h2>{{ $product->name }}</h2>
                <div class="price">{{ number_format($product->price_cents / 100, 2) }} {{ $product->currency }}</div>
                <p class="tax">{{ __('site.storefront.product.tax_included') }}</p>

                @if ($product->stock_quantity > 0)
                    <div class="stock">
                        <span class="dot"></span>
                        @if ($product->stock_quantity < 10)
                            {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                        @else
                            {{ __('site.storefront.product.in_stock_full') }}
                        @endif
                    </div>
                @endif

                @if ($product->description)
                    <p class="desc">{{ $product->description }}</p>
                @endif

                <form method="post" action="/cart/add/{{ $product->slug }}" class="add-form">
                    @csrf
                    <button type="submit" class="add-btn">{{ __('site.storefront.product.add_to_cart') }}</button>
                    <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                </form>

                <div class="perks">
                    <div class="perk">
                        <span class="label">{{ __('site.storefront.value_props.shipping_title') }}</span>
                        <span class="value">{{ __('site.storefront.value_props.shipping_sub') }}</span>
                    </div>
                    <div class="perk">
                        <span class="label">{{ __('site.storefront.value_props.returns_title') }}</span>
                        <span class="value">{{ __('site.storefront.value_props.returns_sub') }}</span>
                    </div>
                    <div class="perk">
                        <span class="label">{{ __('site.storefront.value_props.checkout_title') }}</span>
                        <span class="value">{{ __('site.storefront.value_props.checkout_sub') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
