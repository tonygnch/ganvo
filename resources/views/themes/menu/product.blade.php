@php $title = $product->name; @endphp
@extends('themes.menu.layout')

@section('content')
    <style>
        .dish-page {
            max-width: 880px;
            margin: 0 auto;
            padding: 3rem 1.5rem 5rem;
        }
        .breadcrumb {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: center;
            margin: 0 0 2rem;
        }
        .breadcrumb a:hover { color: var(--ink); }
        .breadcrumb .sep { margin: 0 .625rem; }

        .dish {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .dish-image {
            position: relative;
            aspect-ratio: 1 / 1;
            background: var(--paper-deep);
            overflow: hidden;
        }
        .dish-image img { width: 100%; height: 100%; object-fit: cover; }
        .dish-image .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--ink-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            font-family: var(--display);
            font-style: italic;
        }

        .dish-info { padding-top: .75rem; }
        .dish-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 .75rem;
        }
        .dish-info h2 {
            font-family: var(--display);
            font-style: italic;
            font-weight: 700;
            font-size: clamp(2rem, 4vw, 2.75rem);
            line-height: 1.05;
            letter-spacing: -0.005em;
            margin: 0 0 1rem;
            color: var(--ink);
        }
        .dish-price {
            font-family: var(--display);
            font-weight: 600;
            font-size: 1.75rem;
            color: var(--ink);
            margin: 0 0 .25rem;
            font-variant-numeric: tabular-nums;
        }
        .dish-tax {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 2rem;
        }
        .dish-desc {
            color: var(--ink-soft);
            line-height: 1.7;
            font-size: 1.0625rem;
            margin: 0 0 2.5rem;
            font-style: italic;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--rule);
        }
        .stock {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }
        .stock .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--primary);
        }
        .add-form { display: flex; flex-direction: column; gap: 0; }
        .add-row { display: flex; gap: .5rem; }
        .add-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .add-btn {
            flex: 1;
            background: var(--ink);
            color: var(--paper);
            border: 0;
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            cursor: pointer;
            font-weight: 600;
            transition: background-color .2s ease;
        }
        .add-btn:hover { background: var(--primary-strong); }
        .wishlist-btn {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--rule);
            padding: 0 1.25rem;
            cursor: pointer;
            font-size: 1.125rem;
            transition: color .2s ease, border-color .2s ease;
        }
        .wishlist-btn:hover { color: var(--primary); border-color: var(--primary); }

        @media (max-width: 720px) {
            .dish { grid-template-columns: 1fr; gap: 2rem; }
        }
    </style>

    <div class="dish-page">
        <div class="breadcrumb">
            <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
            <span class="sep">·</span>
            <span>{{ $product->name }}</span>
        </div>

        <div class="dish">
            <div class="dish-image">
                @include('storefront.partials.product-gallery')
            </div>

            <div class="dish-info">
                <p class="dish-eyebrow">{{ $tenant->name }}</p>
                <h2>{{ $product->name }}</h2>
                <div class="dish-price"><span data-vp-price>@money($product->price_cents)</span></div>
                <p class="dish-tax">{{ __('site.storefront.product.tax_included') }}</p>

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
                    <p class="dish-desc">{{ $product->description }}</p>
                @endif

                <form method="post" action="/cart/add/{{ $product->slug }}" class="add-form">
                    @csrf
                    @include('storefront.partials.variant-picker')
                    <div class="add-row">
                        <button type="submit" class="add-btn" data-vp-submit>{{ __('site.storefront.product.add_to_cart') }}</button>
                        <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
