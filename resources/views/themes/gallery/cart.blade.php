@php
    $title = __('site.cart.title');
    $subtotal = $total_cents;
    $shipping = $subtotal >= 5000 ? 0 : 500;
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
@endphp
@extends('themes.gallery.layout')

@section('content')
    <style>
        .cart-page { max-width: 1280px; margin: 0 auto; padding: 4rem 2.5rem 6rem; }
        .cart-head { margin: 0 0 3rem; }
        .cart-head .eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin: 0 0 .75rem;
        }
        .cart-head h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 600;
            letter-spacing: -0.025em;
            margin: 0;
        }
        .cart-head .summary-line {
            color: var(--text-muted);
            margin: .375rem 0 0;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 3rem;
            align-items: start;
        }
        .items {
            border-top: 1px solid var(--hair);
            border-bottom: 1px solid var(--hair);
        }
        .item {
            display: grid;
            grid-template-columns: 110px 1fr auto;
            gap: 1.5rem;
            padding: 1.75rem 0;
            border-bottom: 1px solid var(--hair);
            align-items: center;
        }
        .item:last-child { border-bottom: 0; }
        .item-image {
            aspect-ratio: 1;
            background: var(--muted);
            overflow: hidden;
        }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-image .placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.6rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-soft);
        }
        .item-details h3 {
            margin: 0 0 .375rem;
            font-size: 1.0625rem;
            font-weight: 500;
            letter-spacing: -0.005em;
        }
        .item-details .unit { color: var(--text-muted); font-size: 0.875rem; margin: 0 0 .5rem; }
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .625rem;
        }
        .item-subtotal {
            font-size: 1.125rem;
            font-weight: 500;
            color: var(--text);
            font-variant-numeric: tabular-nums;
        }
        .item-controls {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.875rem;
        }
        .qty-step {
            background: var(--surface);
            border: 1px solid var(--hair);
            color: var(--text);
            width: 28px; height: 28px;
            cursor: pointer;
            font-size: 0.875rem;
            line-height: 1;
            transition: border-color .15s ease, color .15s ease;
        }
        .qty-step:hover:not(:disabled) { border-color: var(--text); color: var(--primary); }
        .qty-step:disabled { opacity: .35; cursor: not-allowed; }
        .qty-display {
            min-width: 1.75ch;
            text-align: center;
            color: var(--text);
        }
        .remove-btn {
            background: transparent;
            border: 0;
            color: var(--text-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            cursor: pointer;
            padding: 0;
        }
        .remove-btn:hover { color: var(--text); }

        .summary {
            background: var(--surface);
            border: 1px solid var(--hair);
            padding: 1.75rem;
            position: sticky;
            top: 6rem;
        }
        .summary h2 {
            margin: 0 0 1.5rem;
            font-size: 0.6875rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: .625rem 0;
            font-size: 0.9375rem;
        }
        .summary-row .num { font-variant-numeric: tabular-nums; }
        .summary-row.free .num { color: var(--primary); font-weight: 600; }
        .summary-hint { font-size: 0.75rem; color: var(--text-soft); margin: .375rem 0 .75rem; }
        .summary-row.total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--hair);
            font-size: 1.125rem;
            font-weight: 600;
        }
        .summary-row.total .num { font-size: 1.5rem; font-variant-numeric: tabular-nums; }

        .checkout-btn {
            display: block;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            text-align: center;
            cursor: pointer;
            transition: opacity .2s ease;
        }
        .checkout-btn:hover { opacity: .85; }
        .keep-shopping {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .keep-shopping a { color: var(--text); border-bottom: 1px solid var(--text); padding-bottom: 1px; }

        .empty {
            text-align: center;
            padding: 6rem 2rem;
            color: var(--text-soft);
            border: 1px dashed var(--hair);
        }
        .empty h2 { color: var(--text); font-size: 1.5rem; margin: 0 0 .5rem; }
        .empty p { margin: 0 0 1.5rem; }

        @media (max-width: 900px) {
            .cart-layout { grid-template-columns: 1fr; }
            .item { grid-template-columns: 80px 1fr; }
            .item-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; }
        }
    </style>

    <div class="cart-page">
        <header class="cart-head">
            <p class="eyebrow">{{ __('site.cart.eyebrow') }}</p>
            <h1>{{ __('site.cart.title') }}</h1>
            @if ($items->isNotEmpty())
                <p class="summary-line">{{ trans_choice('site.cart.summary_line', $items->sum('quantity'), ['count' => $items->sum('quantity')]) }}</p>
            @endif
        </header>

        @if ($items->isEmpty())
            <div class="empty">
                <h2>{{ __('site.cart.empty_title') }}</h2>
                <p>{{ __('site.cart.empty_body') }}</p>
                <a href="/" class="checkout-btn" style="display: inline-block; width: auto; padding: 1rem 2rem;">{{ __('site.common.continue_shopping') }}</a>
            </div>
        @else
            <div class="cart-layout">
                <section class="items">
                    @foreach ($items as $row)
                        @php
                            $product = $row['product'];
                            $variant = $row['variant'] ?? null;
                            $lineId = $row['line_id'];
                            $unitCents = $row['unit_price_cents'];
                        @endphp
                        <div class="item">
                            <div class="item-image">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <span class="placeholder">{{ __('site.storefront.product.no_image') }}</span>
                                @endif
                            </div>
                            <div class="item-details">
                                <h3>{{ $product->name }}</h3>
                                @if ($variant)
                                    <p class="variant">{{ $variant->label }}</p>
                                @endif
                                <p class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($unitCents, $displayRate, $displayCurrency)]) }}</p>
                                <form method="post" action="/cart/{{ $lineId }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="remove-btn">{{ __('site.cart.remove') }}</button>
                                </form>
                            </div>
                            <div class="item-actions">
                                <div class="item-subtotal">@money($row['subtotal_cents'])</div>
                                <div class="item-controls">
                                    <form method="post" action="/cart/{{ $lineId }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $row['quantity'] - 1 }}">
                                        <button type="submit" class="qty-step" @if($row['quantity'] <= 1) disabled @endif>−</button>
                                    </form>
                                    <span class="qty-display">{{ $row['quantity'] }}</span>
                                    <form method="post" action="/cart/{{ $lineId }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="quantity" value="{{ $row['quantity'] + 1 }}">
                                        <button type="submit" class="qty-step">+</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </section>

                <aside class="summary">
                    <h2>{{ __('site.cart.summary') }}</h2>
                    <div class="summary-row">
                        <span>{{ __('site.cart.subtotal') }}</span>
                        <span class="num">@money($subtotal)</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}">
                        <span>{{ __('site.cart.shipping') }}</span>
                        <span class="num">@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    @if ($shipping > 0)
                        <div class="summary-hint">{{ __('site.cart.free_shipping_at') }}</div>
                    @endif
                    @if ($discount && $discountCents > 0)
                        <div class="summary-row discount">
                            <span>{{ $discount->name }}</span>
                            <span class="num">−@money($discountCents)</span>
                        </div>
                    @endif

                    @include('storefront.partials.discount-form')

                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="num">@money($grand)</span>
                    </div>
                    <a href="/checkout" class="checkout-btn">{{ __('site.cart.checkout') }}</a>
                    <p class="keep-shopping"><a href="/">{{ __('site.common.continue_shopping') }}</a></p>
                </aside>
            </div>
        @endif
    </div>
@endsection
