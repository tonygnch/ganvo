@php $title = $product->name; @endphp
@extends('themes.gallery.layout')

@section('content')
    <style>
        .product-page { padding: 4rem 0 6rem; }
        .breadcrumb {
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin-bottom: 3rem;
        }
        .breadcrumb a:hover { color: var(--text); }
        .breadcrumb .sep { margin: 0 .625rem; }

        .product {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 4rem;
            align-items: start;
        }
        .gallery-main {
            position: relative;
            aspect-ratio: 4 / 5;
            background: var(--muted);
            overflow: hidden;
        }
        .gallery-main img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-main .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .info {
            padding-top: 2rem;
            position: sticky;
            top: 6rem;
        }
        .info .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 1rem;
        }
        .info h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            letter-spacing: -0.015em;
            margin: 0 0 1.25rem;
            line-height: 1.1;
        }
        .info .price {
            font-size: 1.5rem;
            font-weight: 500;
            margin: 0 0 .25rem;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }
        .info .tax {
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0 0 2rem;
        }
        .info .desc {
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 1.0625rem;
            margin: 0 0 2.5rem;
            padding-bottom: 2.5rem;
            border-bottom: 1px solid var(--hair);
        }

        .stock {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: 0.75rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .stock .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
        }

        .add-form { display: flex; flex-direction: column; gap: 0; }
        .add-row { display: flex; gap: .625rem; }
        .add-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .add-btn {
            flex: 1;
            background: var(--text);
            color: var(--bg);
            border: 0;
            padding: 1.125rem 1.5rem;
            font-size: 0.75rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            cursor: pointer;
            font-weight: 600;
            transition: background-color .2s ease;
        }
        .add-btn:hover { background: var(--primary); }
        .wishlist-btn {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--hair);
            padding: 0 1.5rem;
            cursor: pointer;
            font-size: 1.125rem;
            transition: border-color .2s ease, color .2s ease;
        }
        .wishlist-btn:hover { color: var(--primary); border-color: var(--primary); }

        .perks {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .perk {
            padding: 1rem 0;
            border-top: 1px solid var(--hair);
        }
        .perk .label {
            font-size: 0.6875rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .25rem;
        }
        .perk .value {
            font-size: 0.875rem;
            color: var(--text);
        }

        @media (max-width: 900px) {
            .product { grid-template-columns: 1fr; gap: 2.5rem; }
            .info { position: static; padding-top: 0; }
            .perks { grid-template-columns: 1fr; }
        }
    </style>

    <div class="container product-page">
        <div class="breadcrumb">
            <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
            <span class="sep">/</span>
            <span>{{ $product->name }}</span>
        </div>

        <div class="product">
            <div class="gallery-main">
                @include('storefront.partials.product-gallery')
            </div>

            <div class="info">
                <p class="eyebrow">{{ $tenant->name }}</p>
                <h2>{{ $product->name }}</h2>
                <div class="price"><span data-vp-price>@money($product->price_cents)</span></div>
                <p class="tax">{{ __('site.storefront.product.tax_included') }}</p>

                @if (! $product->hasVariants() && $product->stock_quantity > 0)
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
                    @include('storefront.partials.variant-picker')
                    <div class="add-row">
                        <button type="submit" class="add-btn" data-vp-submit>{{ __('site.storefront.product.add_to_cart') }}</button>
                        <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                    </div>
                </form>

                <div class="perks">
                    <div class="perk">
                        <p class="label">{{ __('site.storefront.value_props.shipping_title') }}</p>
                        <p class="value">{{ __('site.storefront.value_props.shipping_sub') }}</p>
                    </div>
                    <div class="perk">
                        <p class="label">{{ __('site.storefront.value_props.returns_title') }}</p>
                        <p class="value">{{ __('site.storefront.value_props.returns_sub') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
