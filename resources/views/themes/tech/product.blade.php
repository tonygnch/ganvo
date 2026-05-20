@php $title = $product->name; @endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .product-page { padding: 2.5rem 0 5rem; }
        .breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--text-soft);
            margin: 0 0 2rem;
        }
        .breadcrumb a:hover { color: var(--text); }
        .breadcrumb .sep { color: var(--hair-strong); }

        .product {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .gallery {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            aspect-ratio: 4 / 3;
        }
        .gallery img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-family: var(--mono);
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .info { position: sticky; top: 5rem; }
        .info .pill {
            display: inline-block;
            background: var(--primary-soft);
            color: var(--primary-strong);
            font-family: var(--mono);
            font-size: 0.6875rem;
            font-weight: 700;
            padding: .25rem .625rem;
            border-radius: 4px;
            margin: 0 0 1rem;
        }
        .info h2 {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 .75rem;
            line-height: 1.15;
        }
        .info .price-row {
            display: flex;
            align-items: baseline;
            gap: .75rem;
            margin: 0 0 .25rem;
        }
        .info .price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }
        .info .tax {
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
        }
        .info .desc {
            color: var(--text-muted);
            line-height: 1.7;
            font-size: 0.9375rem;
            margin: 1.5rem 0 2rem;
        }

        .spec-table {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 10px;
            overflow: hidden;
            margin: 0 0 2rem;
        }
        .spec-table .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: .75rem 1rem;
            border-bottom: 1px solid var(--hair);
            font-size: 0.8125rem;
        }
        .spec-table .row:last-child { border-bottom: 0; }
        .spec-table .row dt {
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin: 0;
        }
        .spec-table .row dd {
            margin: 0;
            text-align: right;
            font-family: var(--mono);
            color: var(--text);
            font-weight: 600;
        }

        .add-form { display: flex; gap: .5rem; }
        .add-btn {
            flex: 1;
            background: var(--text);
            color: var(--bg);
            border: 0;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .15s ease;
        }
        .add-btn:hover { background: var(--primary); }
        .wishlist-btn {
            background: var(--surface);
            border: 1px solid var(--hair);
            color: var(--text);
            padding: 0 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.125rem;
            transition: color .15s ease, border-color .15s ease;
        }
        .wishlist-btn:hover { color: var(--primary); border-color: var(--primary); }

        @media (max-width: 880px) {
            .product { grid-template-columns: 1fr; }
            .info { position: static; }
        }
    </style>

    <div class="container product-page">
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
                @if ($product->stock_quantity > 0)
                    <span class="pill">
                        @if ($product->stock_quantity < 10)
                            {{ __('site.storefront.product.in_stock_low', ['count' => $product->stock_quantity]) }}
                        @else
                            {{ __('site.storefront.product.in_stock_full') }}
                        @endif
                    </span>
                @endif
                <h2>{{ $product->name }}</h2>
                <div class="price-row">
                    <span class="price">@money($product->price_cents)</span>
                    <span class="tax">{{ __('site.storefront.product.tax_included') }}</span>
                </div>

                @if ($product->description)
                    <p class="desc">{{ $product->description }}</p>
                @endif

                <dl class="spec-table">
                    <div class="row"><dt>SKU</dt><dd>#{{ str_pad((string) $product->id, 6, '0', STR_PAD_LEFT) }}</dd></div>
                    <div class="row"><dt>{{ __('site.storefront.value_props.shipping_title') }}</dt><dd>{{ __('site.storefront.value_props.shipping_sub') }}</dd></div>
                    <div class="row"><dt>{{ __('site.storefront.value_props.returns_title') }}</dt><dd>{{ __('site.storefront.value_props.returns_sub') }}</dd></div>
                    <div class="row"><dt>Stock</dt><dd>{{ $product->stock_quantity }}</dd></div>
                </dl>

                <form method="post" action="/cart/add/{{ $product->slug }}" class="add-form">
                    @csrf
                    <button type="submit" class="add-btn">{{ __('site.storefront.product.add_to_cart') }}</button>
                    <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                </form>
            </div>
        </div>
    </div>
@endsection
