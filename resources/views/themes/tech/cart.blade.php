@php
    $title = __('site.cart.title');
    $subtotal = $total_cents;
    $shipping = $subtotal >= 5000 ? 0 : 500;
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .cart-page { max-width: 1280px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
        .cart-head { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin: 0 0 2.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--hair); }
        .cart-head h1 { margin: 0; font-size: 1.625rem; font-weight: 700; letter-spacing: -0.02em; }
        .cart-head .summary-line { font-family: var(--mono); font-size: 0.8125rem; color: var(--text-soft); }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        .item {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 1rem;
            display: grid;
            grid-template-columns: 88px 1fr auto;
            gap: 1rem;
            margin: 0 0 .875rem;
            align-items: center;
        }
        .item-thumb {
            width: 88px; height: 88px;
            background: var(--surface-2);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .item-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .item-thumb .placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--mono);
            font-size: 0.625rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-soft);
        }
        .item-meta h3 { margin: 0 0 .25rem; font-size: 1rem; font-weight: 600; letter-spacing: -0.005em; }
        .item-meta .sku { font-family: var(--mono); font-size: 0.6875rem; color: var(--text-soft); margin: 0 0 .375rem; }
        .item-meta .unit { font-size: 0.8125rem; color: var(--text-muted); margin: 0 0 .5rem; }
        .qty-controls { display: inline-flex; align-items: center; gap: .25rem; }
        .qty-step {
            width: 28px; height: 28px;
            background: var(--surface-2);
            border: 1px solid var(--hair);
            border-radius: 6px;
            color: var(--text);
            cursor: pointer;
            font-family: inherit;
            transition: border-color .15s ease;
        }
        .qty-step:hover:not(:disabled) { border-color: var(--text); }
        .qty-step:disabled { opacity: .35; cursor: not-allowed; }
        .qty-display {
            font-family: var(--mono);
            font-weight: 600;
            color: var(--text);
            min-width: 2ch;
            text-align: center;
            font-size: 0.875rem;
        }
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .5rem;
        }
        .item-subtotal {
            font-size: 1.125rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }
        .remove-btn {
            background: transparent;
            border: 0;
            color: var(--text-soft);
            font-family: var(--mono);
            font-size: 0.6875rem;
            letter-spacing: 0.06em;
            cursor: pointer;
            padding: 0;
        }
        .remove-btn:hover { color: var(--text); }

        .summary {
            background: var(--surface);
            border: 1px solid var(--hair);
            border-radius: 12px;
            padding: 1.5rem;
            position: sticky;
            top: 6rem;
        }
        .summary h2 {
            margin: 0 0 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-soft);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .5rem 0;
            font-size: 0.9375rem;
        }
        .summary-row .num { font-family: var(--mono); font-variant-numeric: tabular-nums; }
        .summary-row.free .num { color: var(--primary); font-weight: 700; }
        .summary-hint { font-size: 0.75rem; color: var(--text-soft); margin: .25rem 0 .5rem; }
        .summary-row.total {
            margin-top: .75rem;
            padding-top: .875rem;
            border-top: 1px dashed var(--hair);
            font-size: 1.0625rem;
            font-weight: 700;
        }
        .summary-row.total .num { font-size: 1.5rem; }
        .checkout-btn {
            display: block;
            width: 100%;
            background: var(--text);
            color: var(--bg);
            border: 0;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-top: 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: background-color .15s ease;
        }
        .checkout-btn:hover { background: var(--primary); }
        .keep-shopping { text-align: center; margin-top: .75rem; font-size: 0.8125rem; color: var(--text-muted); }
        .keep-shopping a { color: var(--primary); }

        .empty { text-align: center; padding: 4rem 1rem; border: 1px dashed var(--hair); border-radius: 12px; color: var(--text-muted); }
        .empty h2 { color: var(--text); margin: 0 0 .5rem; font-size: 1.25rem; }
        .empty .cta {
            display: inline-block;
            margin-top: 1rem;
            padding: .75rem 1.5rem;
            background: var(--text);
            color: var(--bg);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        @media (max-width: 880px) {
            .cart-layout { grid-template-columns: 1fr; }
            .item { grid-template-columns: 72px 1fr; }
            .item-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; }
        }
    </style>

    <div class="cart-page">
        <div class="cart-head">
            <h1>{{ __('site.cart.title') }}</h1>
            @if ($items->isNotEmpty())
                <span class="summary-line">{{ trans_choice('site.cart.summary_line', $items->sum('quantity'), ['count' => $items->sum('quantity')]) }}</span>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="empty">
                <h2>{{ __('site.cart.empty_title') }}</h2>
                <p>{{ __('site.cart.empty_body') }}</p>
                <a href="/" class="cta">{{ __('site.common.continue_shopping') }}</a>
            </div>
        @else
            <div class="cart-layout">
                <section>
                    @foreach ($items as $row)
                        @php
                            $product = $row['product'];
                            $variant = $row['variant'] ?? null;
                            $lineId = $row['line_id'];
                            $unitCents = $row['unit_price_cents'];
                        @endphp
                        <div class="item">
                            <div class="item-thumb">
                                @if ($product->image_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <span class="placeholder">N/A</span>
                                @endif
                            </div>
                            <div class="item-meta">
                                <h3>{{ $product->name }}</h3>
                                @if ($variant)
                                    <p class="variant">{{ $variant->label }}</p>
                                @endif
                                <p class="sku">SKU #{{ $variant?->sku ?: str_pad((string) $product->id, 6, '0', STR_PAD_LEFT) }}</p>
                                <p class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($unitCents, $displayRate, $displayCurrency)]) }}</p>
                                <div class="qty-controls">
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
                            <div class="item-actions">
                                <div class="item-subtotal">@money($row['subtotal_cents'])</div>
                                <form method="post" action="/cart/{{ $lineId }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="remove-btn">{{ __('site.cart.remove') }}</button>
                                </form>
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
                    @if ($shipping > 0)<p class="summary-hint">{{ __('site.cart.free_shipping_at') }}</p>@endif
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
