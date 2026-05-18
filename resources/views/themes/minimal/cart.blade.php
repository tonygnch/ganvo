@php
    $title = __('site.cart.title');
@endphp
@extends('themes.minimal.layout')

@section('content')
    <style>
        .cart-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 2rem 5rem;
        }
        .cart-head {
            text-align: center;
            margin-bottom: 3rem;
        }
        .cart-head .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .75rem;
        }
        .cart-head h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: clamp(2.25rem, 4vw, 3.25rem);
            letter-spacing: -0.01em;
            margin: 0 0 .75rem;
            line-height: 1.1;
        }
        .cart-head .summary-line {
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.125rem;
            margin: 0;
        }
        .cart-head .hairline { width: 40px; height: 1px; background: var(--text); margin: 1.25rem auto 0; }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 4rem;
            align-items: start;
        }

        /* -------- Items -------- */
        .items {
            border-top: 1px solid var(--hair);
        }
        .item {
            display: grid;
            grid-template-columns: 96px 1fr auto;
            gap: 1.5rem;
            padding: 1.75rem 0;
            border-bottom: 1px solid var(--hair);
            align-items: center;
        }
        .item-image {
            aspect-ratio: 4 / 5;
            width: 96px; height: auto;
            background: var(--muted);
            overflow: hidden;
            position: relative;
        }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-image .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-soft);
            font-size: 0.625rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        .item-details h3 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            font-size: 1.375rem;
            margin: 0 0 .375rem;
            letter-spacing: 0.01em;
            line-height: 1.2;
        }
        .item-details h3 a { color: var(--text); transition: color .2s ease; }
        .item-details h3 a:hover { color: var(--primary); }
        .item-details .unit {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1rem;
        }
        .item-subtotal {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.375rem;
            color: var(--primary);
        }
        .item-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* -------- Quantity stepper (minimal, hairline) -------- */
        .qty {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-bottom: 1px solid var(--text);
            padding-bottom: 2px;
        }
        .qty form { margin: 0; display: inline-flex; }
        .qty button {
            width: 22px; height: 22px;
            background: transparent;
            border: 0;
            cursor: pointer;
            color: var(--text);
            font-size: 0.875rem;
            transition: color .15s ease;
            line-height: 1;
            padding: 0;
        }
        .qty button:hover { color: var(--primary); }
        .qty .value {
            min-width: 1.5rem;
            text-align: center;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.125rem;
            color: var(--text);
        }
        .remove-btn {
            background: transparent;
            border: 0;
            cursor: pointer;
            color: var(--text-soft);
            font-size: 0.625rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            padding: 0;
            transition: color .15s ease;
        }
        .remove-btn:hover { color: var(--text); }

        /* -------- Summary -------- */
        .summary {
            position: sticky;
            top: 8rem;
            padding-top: 0;
        }
        .summary h2 {
            font-size: 0.6875rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            margin: 0 0 2rem;
            font-weight: 500;
            color: var(--text);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .75rem 0;
            font-size: 0.875rem;
            color: var(--text-muted);
            border-bottom: 1px solid var(--hair);
        }
        .summary-row:first-of-type { border-top: 1px solid var(--hair); }
        .summary-row .label {
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .summary-row .num {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.0625rem;
            color: var(--text);
        }
        .summary-row.free .num { color: var(--primary); font-style: italic; }
        .summary-row.total {
            border-bottom: 1px solid var(--text);
            padding: 1.25rem 0;
            margin-top: .5rem;
        }
        .summary-row.total .label {
            font-size: 0.75rem;
            letter-spacing: 0.25em;
            color: var(--text);
            font-weight: 500;
        }
        .summary-row.total .num {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 2rem;
            color: var(--primary);
        }
        .summary-hint {
            color: var(--text-soft);
            font-size: 0.625rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            margin: .75rem 0 1.5rem;
            text-align: right;
        }
        .checkout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            background: var(--text);
            color: white;
            border: 0;
            padding: 1.125rem;
            font-size: 0.7rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            text-decoration: none;
            width: 100%;
            cursor: pointer;
            font-family: system-ui, sans-serif;
            transition: background-color .2s ease;
            margin-top: 1.25rem;
        }
        .checkout-btn:hover { background: var(--primary); }
        .keep-shopping {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1rem;
            transition: color .2s ease;
        }
        .keep-shopping:hover { color: var(--text); }

        .perks-row {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--hair);
            display: flex;
            flex-direction: column;
            gap: .75rem;
            color: var(--text-muted);
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }
        .perks-row span { display: block; }

        /* -------- Empty state -------- */
        .empty {
            text-align: center;
            padding: 5rem 1.5rem;
            max-width: 460px;
            margin: 0 auto;
        }
        .empty .ornament {
            width: 36px; height: 1px;
            background: var(--text);
            margin: 0 auto 2.5rem;
        }
        .empty h2 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 400;
            font-size: 2rem;
            margin: 0 0 1rem;
            letter-spacing: -0.01em;
        }
        .empty p {
            color: var(--text-muted);
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-style: italic;
            font-size: 1.125rem;
            margin: 0 0 2.5rem;
        }
        .empty .btn {
            display: inline-block;
            background: var(--text);
            color: white;
            padding: 1rem 2.5rem;
            font-size: 0.7rem;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            transition: background-color .2s ease;
        }
        .empty .btn:hover { background: var(--primary); }

        @media (max-width: 880px) {
            .cart-layout { grid-template-columns: 1fr; gap: 2.5rem; }
            .summary { position: static; }
            .cart-head { margin-bottom: 2rem; }
        }
        @media (max-width: 560px) {
            .cart-page { padding: 2.5rem 1.25rem 3rem; }
            .item { grid-template-columns: 72px 1fr; gap: 1rem; padding: 1.25rem 0; }
            .item-image { width: 72px; }
            .item-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; align-items: center; gap: 1rem; }
        }
    </style>

    <div class="cart-page">
        <div class="cart-head">
            <p class="eyebrow">{{ __('site.storefront.shop_all.eyebrow') }}</p>
            <h1>{{ __('site.cart.title') }}</h1>
            @if ($items->isNotEmpty())
                @php $totalQty = $items->sum('quantity'); @endphp
                <p class="summary-line">{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</p>
            @endif
            <div class="hairline"></div>
        </div>

        @if ($items->isEmpty())
            <div class="empty">
                <div class="ornament"></div>
                <h2>{{ __('site.cart.empty_title') }}</h2>
                <p>{{ __('site.cart.empty_sub') }}</p>
                <a href="/" class="btn">{{ __('site.cart.start_shopping') }}</a>
            </div>
        @else
            <div class="cart-layout">
                <div class="items">
                    @foreach ($items as $row)
                        @php
                            $product = $row['product'];
                            $qty = $row['quantity'];
                        @endphp
                        <div class="item">
                            <div class="item-image">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="">
                                @else
                                    <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                                @endif
                            </div>
                            <div class="item-details">
                                <h3><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></h3>
                                <div class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($product->price_cents, $displayRate, $displayCurrency)]) }}</div>
                            </div>
                            <div class="item-actions">
                                <div class="item-subtotal">@money($row['subtotal_cents'])</div>
                                <div class="item-controls">
                                    <div class="qty">
                                        <form method="post" action="/cart/{{ $product->id }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ max(0, $qty - 1) }}">
                                            <button type="submit" aria-label="{{ __('site.cart.decrease') }}">−</button>
                                        </form>
                                        <span class="value">{{ $qty }}</span>
                                        <form method="post" action="/cart/{{ $product->id }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ $qty + 1 }}">
                                            <button type="submit" aria-label="{{ __('site.cart.increase') }}">+</button>
                                        </form>
                                    </div>
                                    <form method="post" action="/cart/{{ $product->id }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="remove-btn" aria-label="{{ __('site.cart.remove') }}">{{ __('site.cart.remove') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @php
                    $subtotal = $total_cents;
                    $shipping = $subtotal >= 5000 ? 0 : 500;
                    $grand = $subtotal + $shipping;
                @endphp
                <aside class="summary">
                    <h2>{{ __('site.cart.summary') }}</h2>
                    <div class="summary-row">
                        <span class="label">{{ __('site.cart.subtotal') }}</span>
                        <span class="num">@money($subtotal)</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}">
                        <span class="label">{{ __('site.cart.shipping') }}</span>
                        <span class="num">@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    @if ($shipping > 0)
                        <div class="summary-hint">{{ __('site.cart.free_shipping_at') }}</div>
                    @endif
                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="num">@money($grand)</span>
                    </div>
                    <div class="summary-hint">{{ __('site.cart.tax_at_checkout') }}</div>

                    <a href="/checkout" class="checkout-btn">
                        <span>{{ __('site.cart.checkout') }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="/" class="keep-shopping">{{ __('site.cart.keep_shopping') }}</a>

                    <div class="perks-row">
                        <span>{{ __('site.cart.perk_shipping') }}</span>
                        <span>{{ __('site.cart.perk_returns') }}</span>
                        <span>{{ __('site.cart.perk_fast') }}</span>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
