@php
    $title = $product->name;
@endphp
@extends('themes.default.layout')

@section('content')
    <style>
        .product-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--text-soft);
            margin-bottom: 1.5rem;
        }
        .breadcrumb a { color: var(--text-muted); }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb .sep { margin: 0 .5rem; color: var(--text-soft); }

        .product {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        .product-gallery {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
        }
        .product-image {
            aspect-ratio: 1;
            background: linear-gradient(135deg, var(--muted), var(--surface));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .product-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: .375rem .75rem;
            border-radius: 9999px;
        }

        .product-info { padding: 1rem 0; }
        .product-info .eyebrow {
            color: var(--primary-strong);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin: 0 0 .5rem;
        }
        .product-info h2 {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 1rem;
        }
        .product-info .price-large {
            color: var(--primary-strong);
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 1.5rem;
        }
        .product-info .price-large small {
            color: var(--text-soft);
            font-size: 0.875rem;
            font-weight: 500;
            margin-left: .5rem;
        }
        .product-info .desc {
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .stock {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }
        .stock .dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
        }
        .stock.low .dot { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18); }

        .add-form { display: flex; gap: .75rem; align-items: stretch; }
        .add-btn {
            flex: 1;
            background: var(--primary);
            color: white;
            border: 0;
            padding: 1rem 1.5rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: transform .12s ease, background-color .2s ease, box-shadow .15s ease;
        }
        .add-btn:hover {
            background: var(--primary-strong);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -6px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        .wishlist-btn {
            background: var(--muted);
            color: var(--text);
            border: 0;
            padding: 1rem 1.25rem;
            border-radius: .75rem;
            cursor: pointer;
            font-size: 1.125rem;
            transition: background-color .2s ease;
        }
        .wishlist-btn:hover { background: var(--border); }

        .perks {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .perk {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .perk-icon {
            width: 28px; height: 28px;
            border-radius: .5rem;
            background: var(--primary-soft);
            color: var(--primary-strong);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        @media (max-width: 720px) {
            .product { grid-template-columns: 1fr; }
        }
    </style>

    <div class="product-page">
        <div class="breadcrumb">
            <a href="/">{{ __('site.storefront.product.breadcrumb_shop') }}</a>
            <span class="sep">/</span>
            <span>{{ $product->name }}</span>
        </div>

        <div class="product">
            <div class="product-gallery">
                <div class="product-image">
                    @if ($product->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                    @else
                        {{ __('site.storefront.product.no_image') }}
                    @endif
                </div>
                <span class="product-badge">{{ __('site.storefront.product.in_stock_badge') }}</span>
            </div>

            <div class="product-info">
                <p class="eyebrow">{{ $tenant->name }}</p>
                <h2>{{ $product->name }}</h2>
                <div class="price-large">
                    @money($product->price_cents)
                    <small>{{ __('site.storefront.product.tax_included') }}</small>
                </div>

                @if ($product->stock_quantity > 0)
                    <div class="stock {{ $product->stock_quantity < 10 ? 'low' : '' }}">
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
                    <button type="submit" class="add-btn">
                        <span>{{ __('site.storefront.product.add_to_cart') }}</span>
                        <span>·</span>
                        <span>@money($product->price_cents)</span>
                    </button>
                    <button type="button" class="wishlist-btn" aria-label="{{ __('site.storefront.product.wishlist') }}" title="{{ __('site.storefront.product.wishlist') }}">♡</button>
                </form>

                <div class="perks">
                    <div class="perk"><span class="perk-icon">⌁</span> {{ __('site.storefront.product.perks.shipping') }}</div>
                    <div class="perk"><span class="perk-icon">⟲</span> {{ __('site.storefront.product.perks.returns') }}</div>
                    <div class="perk"><span class="perk-icon">⚡</span> {{ __('site.storefront.product.perks.fast') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
