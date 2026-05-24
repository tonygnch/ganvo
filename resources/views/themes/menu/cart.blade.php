@php
    $title = __('site.cart.title');
    $subtotal = $total_cents;
    $shipping = $subtotal >= 5000 ? 0 : 500;
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
@endphp
@extends('themes.menu.layout')

@section('content')
    <style>
        .cart-sheet { max-width: 800px; margin: 0 auto; padding: 4rem 1.5rem 5rem; }
        .cart-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: center;
            margin: 0 0 .75rem;
        }
        .cart-heading {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-style: italic;
            font-size: clamp(2rem, 4vw, 2.75rem);
            text-align: center;
            margin: 0 0 .5rem;
            color: var(--ink);
        }
        .cart-summary-line {
            text-align: center;
            color: var(--ink-soft);
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 1rem;
            margin: 0 0 1rem;
        }
        .ornament {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: var(--ink-soft);
            margin: 0 0 2.5rem;
        }
        .ornament::before, .ornament::after { content: ""; height: 1px; background: var(--rule); width: 70px; }

        .cart-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--rule);
            align-items: start;
        }
        .cart-row-left { min-width: 0; }
        .cart-name {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            font-size: 1.375rem;
            letter-spacing: -0.005em;
            margin: 0 0 .375rem;
            color: var(--ink);
        }
        .cart-unit {
            color: var(--ink-soft);
            font-style: italic;
            font-size: 0.875rem;
            margin: 0 0 .75rem;
        }
        .cart-controls {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            font-size: 0.875rem;
        }
        .qty-step {
            width: 28px; height: 28px;
            background: transparent;
            border: 1px solid var(--rule);
            color: var(--ink);
            cursor: pointer;
            font-family: inherit;
            transition: border-color .15s ease, color .15s ease;
        }
        .qty-step:hover:not(:disabled) { border-color: var(--ink); }
        .qty-step:disabled { opacity: .35; cursor: not-allowed; }
        .qty-display {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            font-size: 1rem;
            min-width: 1.5ch;
            text-align: center;
        }
        .remove-btn {
            background: transparent;
            border: 0;
            color: var(--ink-soft);
            font-size: 0.6875rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            cursor: pointer;
            margin-left: .5rem;
        }
        .remove-btn:hover { color: var(--ink); }

        .cart-price {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            font-size: 1.375rem;
            color: var(--ink);
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--ink);
        }
        .totals-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: baseline;
            padding: .625rem 0;
            color: var(--ink);
            font-size: 0.9375rem;
        }
        .totals-row .label { color: var(--ink-soft); font-style: italic; font-family: 'Playfair Display', Georgia, serif; }
        .totals-row .leader { border-bottom: 1px dotted var(--rule); margin: 0 .75rem .375rem; align-self: end; min-height: 1px; }
        .totals-row .num { font-variant-numeric: tabular-nums; }
        .totals-row.free .num { color: var(--primary-strong); font-weight: 600; }
        .totals-row.total {
            margin-top: .75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--rule);
            font-size: 1.0625rem;
        }
        .totals-row.total .label {
            font-style: normal;
            font-weight: 600;
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink);
        }
        .totals-row.total .num {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: 1.875rem;
        }
        .summary-hint {
            font-size: 0.8125rem;
            color: var(--ink-soft);
            font-style: italic;
            margin: .25rem 0 .5rem;
            text-align: right;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            text-align: center;
            cursor: pointer;
            transition: background-color .2s ease;
        }
        .checkout-btn:hover { background: var(--primary-strong); }

        .keep-shopping {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--ink-soft);
            font-style: italic;
            font-family: 'Playfair Display', Georgia, serif;
        }
        .keep-shopping a { color: var(--ink); border-bottom: 1px solid currentColor; }

        .empty {
            text-align: center;
            padding: 4rem 0;
            color: var(--ink-soft);
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 1.25rem;
        }
        .empty .cta {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 1rem 2rem;
            background: var(--ink);
            color: var(--paper);
            font-size: 0.75rem;
            font-style: normal;
            font-family: inherit;
            letter-spacing: 0.24em;
            text-transform: uppercase;
        }

        @media (max-width: 540px) {
            .cart-row { grid-template-columns: 1fr; }
            .cart-price { text-align: left; }
            .totals-row { grid-template-columns: 1fr auto; }
            .totals-row .leader { display: none; }
        }
    </style>

    <div class="cart-sheet">
        <p class="cart-eyebrow">{{ __('site.cart.eyebrow') }}</p>
        <h1 class="cart-heading">{{ __('site.cart.title') }}</h1>
        @if ($items->isNotEmpty())
            <p class="cart-summary-line">{{ trans_choice('site.cart.summary_line', $items->sum('quantity'), ['count' => $items->sum('quantity')]) }}</p>
        @endif
        <div class="ornament" aria-hidden="true"></div>

        @if ($items->isEmpty())
            <p class="empty">
                {{ __('site.cart.empty_body') }}
                <br><a href="/" class="cta">{{ __('site.common.continue_shopping') }}</a>
            </p>
        @else
            @foreach ($items as $row)
                @php
                    $product = $row['product'];
                    $variant = $row['variant'] ?? null;
                    $lineId = $row['line_id'];
                    $unitCents = $row['unit_price_cents'];
                @endphp
                <div class="cart-row">
                    <div class="cart-row-left">
                        <h3 class="cart-name">
                            {{ $product->name }}
                            @if ($variant)
                                <span class="cart-variant">— {{ $variant->label }}</span>
                            @endif
                        </h3>
                        <p class="cart-unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($unitCents, $displayRate, $displayCurrency)]) }}</p>
                        <div class="cart-controls">
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
                            <form method="post" action="/cart/{{ $lineId }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="remove-btn">{{ __('site.cart.remove') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="cart-price">@money($row['subtotal_cents'])</div>
                </div>
            @endforeach

            <div class="totals">
                <div class="totals-row">
                    <span class="label">{{ __('site.cart.subtotal') }}</span>
                    <span class="leader" aria-hidden="true"></span>
                    <span class="num">@money($subtotal)</span>
                </div>
                <div class="totals-row {{ $shipping === 0 ? 'free' : '' }}">
                    <span class="label">{{ __('site.cart.shipping') }}</span>
                    <span class="leader" aria-hidden="true"></span>
                    <span class="num">@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                </div>
                @if ($shipping > 0)<p class="summary-hint">{{ __('site.cart.free_shipping_at') }}</p>@endif
                @if ($discount && $discountCents > 0)
                    <div class="totals-row discount">
                        <span class="label">{{ $discount->name }}</span>
                        <span class="leader" aria-hidden="true"></span>
                        <span class="num">−@money($discountCents)</span>
                    </div>
                @endif

                @include('storefront.partials.discount-form')

                <div class="totals-row total">
                    <span class="label">{{ __('site.cart.total') }}</span>
                    <span class="leader" aria-hidden="true"></span>
                    <span class="num">@money($grand)</span>
                </div>
            </div>

            <a href="/checkout" class="checkout-btn">{{ __('site.cart.checkout') }}</a>
            <p class="keep-shopping"><a href="/">{{ __('site.common.continue_shopping') }}</a></p>
        @endif
    </div>
@endsection
