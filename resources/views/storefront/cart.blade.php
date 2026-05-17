@php
    $title = __('site.cart.title');
@endphp
@extends("themes.{$theme}.layout")

@section('content')
    <style>
        .cart-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }
        .cart-page h1 {
            margin: 0 0 .25rem;
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .cart-page .summary-line {
            color: var(--text-muted, #57534e);
            font-size: 0.9375rem;
            margin: 0 0 2rem;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 2rem;
            align-items: start;
        }

        /* -------- Items -------- */
        .items {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            overflow: hidden;
        }
        .item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 1rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--border, #e7e5e4);
            align-items: center;
        }
        .item:last-child { border-bottom: 0; }
        .item-image {
            width: 80px; height: 80px;
            border-radius: .625rem;
            background: var(--muted, #f5f5f4);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft, #a8a29e);
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-details h3 {
            margin: 0 0 .25rem;
            font-size: 1rem;
            font-weight: 700;
        }
        .item-details h3 a { color: var(--text, #1c1917); text-decoration: none; }
        .item-details h3 a:hover { color: var(--primary); }
        .item-details .unit {
            color: var(--text-muted, #57534e);
            font-size: 0.875rem;
        }
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .625rem;
        }
        .item-subtotal {
            font-weight: 700;
            font-size: 1.0625rem;
            color: var(--primary-strong, var(--primary));
        }
        .item-controls {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* -------- Quantity stepper -------- */
        .qty {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 9999px;
            overflow: hidden;
            background: var(--surface, white);
        }
        .qty form {
            margin: 0;
            display: inline-flex;
        }
        .qty button {
            width: 30px; height: 30px;
            background: transparent;
            border: 0;
            cursor: pointer;
            color: var(--text-muted, #57534e);
            font-size: 1rem;
            transition: background-color .15s ease, color .15s ease;
        }
        .qty button:hover { background: var(--muted, #f5f5f4); color: var(--text, #1c1917); }
        .qty .value {
            min-width: 28px;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0 .25rem;
        }
        .remove-btn {
            width: 30px; height: 30px;
            background: transparent;
            border: 0;
            cursor: pointer;
            color: var(--text-soft, #a8a29e);
            border-radius: 50%;
            transition: background-color .15s ease, color .15s ease;
            font-size: 1.125rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .remove-btn:hover { background: #fee2e2; color: #dc2626; }

        /* -------- Summary card -------- */
        .summary {
            background: var(--surface, white);
            border: 1px solid var(--border, #e7e5e4);
            border-radius: 1rem;
            padding: 1.5rem;
            position: sticky;
            top: 7rem;
        }
        .summary h2 {
            margin: 0 0 1rem;
            font-size: 1.125rem;
            font-weight: 700;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: .5rem 0;
            font-size: 0.9375rem;
            color: var(--text-muted, #57534e);
        }
        .summary-row .num {
            color: var(--text, #1c1917);
            font-weight: 500;
        }
        .summary-row.free .num { color: #16a34a; font-weight: 600; }
        .summary-divider {
            height: 1px;
            background: var(--border, #e7e5e4);
            margin: .75rem 0;
        }
        .summary-row.total {
            padding-top: .75rem;
        }
        .summary-row.total .label { color: var(--text, #1c1917); font-weight: 700; font-size: 1rem; }
        .summary-row.total .num {
            color: var(--primary-strong, var(--primary));
            font-weight: 800;
            font-size: 1.375rem;
        }
        .summary-hint {
            color: var(--text-soft, #a8a29e);
            font-size: 0.75rem;
            margin: .25rem 0 1.25rem;
            text-align: right;
        }
        .checkout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: var(--primary);
            color: white;
            border: 0;
            padding: 1rem;
            border-radius: .75rem;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            width: 100%;
            cursor: pointer;
            transition: background-color .2s ease, transform .12s ease, box-shadow .15s ease;
        }
        .checkout-btn:hover {
            background: var(--primary-strong, var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 12px 24px -6px color-mix(in srgb, var(--primary) 50%, transparent);
        }
        .keep-shopping {
            display: block;
            text-align: center;
            margin-top: .75rem;
            color: var(--text-muted, #57534e);
            font-size: 0.875rem;
        }
        .keep-shopping:hover { color: var(--primary); }

        .perks-row {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px dashed var(--border, #e7e5e4);
            display: flex;
            flex-direction: column;
            gap: .5rem;
            color: var(--text-muted, #57534e);
            font-size: 0.8125rem;
        }
        .perks-row span { display: inline-flex; align-items: center; gap: .5rem; }

        /* -------- Empty state -------- */
        .empty {
            text-align: center;
            padding: 5rem 1.5rem;
            background: var(--surface, white);
            border: 1px dashed var(--border, #e7e5e4);
            border-radius: 1rem;
        }
        .empty-icon {
            width: 64px; height: 64px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: var(--muted, #f5f5f4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-soft, #a8a29e);
        }
        .empty h2 { margin: 0 0 .5rem; font-weight: 700; }
        .empty p { color: var(--text-muted, #57534e); margin: 0 0 1.5rem; }
        .empty .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: .875rem 1.5rem;
            border-radius: .625rem;
            text-decoration: none;
            font-weight: 600;
        }
        .empty .btn:hover { background: var(--primary-strong, var(--primary)); }

        @media (max-width: 880px) {
            .cart-layout { grid-template-columns: 1fr; }
            .summary { position: static; }
        }
        @media (max-width: 560px) {
            .item { grid-template-columns: 60px 1fr; gap: .875rem; padding: 1rem; }
            .item-image { width: 60px; height: 60px; }
            .item-actions { grid-column: 1 / -1; flex-direction: row; justify-content: space-between; align-items: center; }
        }
    </style>

    <div class="cart-page">
        <h1>{{ __('site.cart.title') }}</h1>
        @if ($items->isNotEmpty())
            @php $totalQty = $items->sum('quantity'); @endphp
            <p class="summary-line">{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</p>
        @endif

        @if ($items->isEmpty())
            <div class="empty">
                <div class="empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 4h2l2.4 12.5a2 2 0 0 0 2 1.5h8.4a2 2 0 0 0 2-1.6L21 8H6"/>
                        <circle cx="10" cy="20" r="1.6"/>
                        <circle cx="18" cy="20" r="1.6"/>
                    </svg>
                </div>
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
                                    No image
                                @endif
                            </div>
                            <div class="item-details">
                                <h3><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></h3>
                                <div class="unit">{{ __('site.cart.unit_each', ['price' => number_format($product->price_cents / 100, 2) . ' ' . $product->currency]) }}</div>
                            </div>
                            <div class="item-actions">
                                <div class="item-subtotal">{{ number_format($row['subtotal_cents'] / 100, 2) }} {{ $product->currency }}</div>
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
                                        <button type="submit" class="remove-btn" aria-label="{{ __('site.cart.remove') }}" title="{{ __('site.cart.remove') }}">×</button>
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
                        <span>{{ __('site.cart.subtotal') }}</span>
                        <span class="num">{{ number_format($subtotal / 100, 2) }} USD</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}">
                        <span>{{ __('site.cart.shipping') }}</span>
                        <span class="num">{{ $shipping === 0 ? __('site.common.free') : number_format($shipping / 100, 2) . ' USD' }}</span>
                    </div>
                    @if ($shipping > 0)
                        <div class="summary-hint">{{ __('site.cart.free_shipping_at') }}</div>
                    @endif
                    <div class="summary-divider"></div>
                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="num">{{ number_format($grand / 100, 2) }} USD</span>
                    </div>
                    <div class="summary-hint">{{ __('site.cart.tax_at_checkout') }}</div>
                    <a href="/checkout" class="checkout-btn">
                        {{ __('site.cart.checkout') }}
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
