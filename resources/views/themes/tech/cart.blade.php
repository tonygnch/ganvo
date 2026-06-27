@php
    $title = __('site.cart.title');
    $subtotal = $total_cents ?? 0;
    $totalQty = $items->sum('quantity');
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal - $discountCents);
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .cart-wrap { padding: 40px 0 80px; }
        .cart-wrap h1 { font-family: var(--archivo); font-weight: 800; font-size: 42px; letter-spacing: -.02em; margin-bottom: 6px; }
        .cart-wrap .sub { font-family: var(--mono); font-size: 12px; color: var(--faint); margin-bottom: 22px; }
        .cart { display: grid; grid-template-columns: 1fr 380px; gap: 48px; align-items: start; }
        .cart-empty { border: 1px solid var(--line); border-radius: 12px; padding: 70px; text-align: center; }
        .cart-empty h2 { font-family: var(--archivo); font-weight: 800; font-size: 26px; margin-bottom: 10px; }
        .cart-empty p { color: var(--muted); margin-bottom: 22px; font-family: var(--mono); font-size: 13px; }

        .line { display: grid; grid-template-columns: 90px 1fr auto; gap: 18px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 14px; align-items: center; }
        .line .img { height: 80px; width: 80px; background: var(--surface2); border-radius: 8px; overflow: hidden; }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .t { font-size: 16px; font-weight: 600; }
        .line .t a:hover { color: var(--accent); }
        .line .m { font-family: var(--mono); font-size: 12px; color: var(--muted); }
        .line .unit { font-family: var(--mono); font-size: 11px; color: var(--faint); margin-top: 2px; }
        .qty { display: inline-flex; border: 1px solid var(--line); border-radius: 7px; margin-top: 10px; }
        .qty form { display: inline-flex; }
        .qty button { width: 30px; height: 30px; background: none; border: none; color: var(--txt); font-size: 15px; cursor: pointer; }
        .qty .n { width: 34px; display: grid; place-items: center; font-family: var(--mono); font-size: 13px; }
        .line .pr { font-family: var(--mono); font-size: 16px; color: var(--accent); text-align: right; }
        .line .rm { font-family: var(--mono); font-size: 11px; color: var(--faint); background: none; border: none; display: block; margin-top: 8px; cursor: pointer; text-align: right; width: 100%; }
        .line .rm:hover { color: #ff5c5c; }

        .summary { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 28px; position: sticky; top: 96px; }
        .summary h3 { font-family: var(--archivo); font-weight: 800; font-size: 22px; margin-bottom: 20px; }
        .summary .promo { display: flex; gap: 8px; margin-bottom: 8px; }
        .summary .promo input { flex: 1; background: var(--bg); border: 1px solid var(--line); border-radius: 7px; padding: 11px; color: var(--txt); font-family: var(--mono); font-size: 12px; }
        .summary .promo button { background: var(--accent); color: #0a0b0e; border: 0; border-radius: 7px; padding: 0 16px; font-weight: 700; font-size: 12px; font-family: var(--mono); cursor: pointer; }
        .summary [hidden] { display: none !important; } /* hidden attr must beat .summary .applied/.r display rules */
        .summary .applied { display: flex; justify-content: space-between; align-items: center; background: var(--surface2); border: 1px solid var(--line); border-radius: 7px; padding: 9px 12px; font-family: var(--mono); font-size: 12px; margin-bottom: 8px; }
        .summary .applied .code { color: var(--accent); }
        .summary .applied form button { background: none; border: none; color: var(--faint); font-family: var(--mono); font-size: 11px; cursor: pointer; }
        .promo-region { margin-bottom: 16px; }
        .promo-msg { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-top: 6px; }
        .summary .r { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 13px; color: var(--muted); }
        .summary .r b, .summary .r span[data-cart-subtotal], .summary .r [data-cart-discount-amount] { font-family: var(--mono); }
        .summary .r small { font-family: var(--mono); font-size: 11px; color: var(--faint); }
        .summary .tot { display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; border-top: 1px solid var(--line); padding-top: 16px; margin: 8px 0 20px; }
        .summary .tot [data-cart-total] { font-family: var(--mono); color: var(--accent); }
        .summary .checkout-btn { display: block; width: 100%; text-align: center; background: var(--accent); color: #0a0b0e; border: 0; border-radius: 6px; padding: 15px; font-weight: 700; font-size: 14px; cursor: pointer; }
        .summary .checkout-btn:hover { box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent); }
        .summary .secure { font-family: var(--mono); font-size: 11px; color: var(--faint); text-align: center; margin-top: 14px; }
        .keep { margin-top: 18px; }

        @media (max-width: 1000px) { .cart { grid-template-columns: 1fr; } .summary { position: static; } }
    </style>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap cart-wrap">
            <h1>{{ __('site.cart.title') }}</h1>
            @if ($items->isNotEmpty())
                <div class="sub">// {{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</div>
            @endif

            @if ($items->isEmpty())
                <div class="cart-empty">
                    <h2>{{ __('site.cart.empty_title') }}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn" href="/">{{ __('site.cart.start_shopping') }}</a>
                </div>
            @else
                <div class="cart" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div>
                        @foreach ($items as $row)
                            @php $product = $row['product']; $variant = $row['variant'] ?? null; $qty = $row['quantity']; $lineId = $row['line_id']; @endphp
                            <div class="line" data-cart-line="{{ $lineId }}">
                                <div class="img">@if ($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="">@endif</div>
                                <div>
                                    <div class="t"><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></div>
                                    @if ($variant)<div class="m">{{ $variant->label }}</div>@endif
                                    <div class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($row['unit_price_cents'], $displayRate ?? 1.0, $displayCurrency ?? $store->currency)]) }}</div>
                                    <div class="qty">
                                        <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>@csrf @method('PATCH')<input type="hidden" name="quantity" value="{{ max(0, $qty - 1) }}" data-qty-value><button type="submit" data-qty-step="-1" aria-label="{{ __('site.cart.decrease') }}">−</button></form>
                                        <span class="n" data-line-qty>{{ $qty }}</span>
                                        <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>@csrf @method('PATCH')<input type="hidden" name="quantity" value="{{ $qty + 1 }}" data-qty-value><button type="submit" data-qty-step="1" aria-label="{{ __('site.cart.increase') }}">+</button></form>
                                    </div>
                                </div>
                                <div style="text-align:right">
                                    <div class="pr" data-line-subtotal>@money($row['subtotal_cents'])</div>
                                    <form method="post" action="/cart/{{ $lineId }}" data-cart-remove>@csrf @method('DELETE')<button type="submit" class="rm">{{ __('site.cart.remove') }}</button></form>
                                </div>
                            </div>
                        @endforeach
                        <a class="btn ghost keep" href="/">← {{ __('site.cart.continue_shopping') }}</a>
                    </div>

                    <aside class="summary">
                        <h3>{{ __('site.cart.summary') }}</h3>
                        <div class="promo-region" data-cart-discount data-applied="{{ $applied_code ? '1' : '' }}">
                            <form method="post" action="/cart/discount" class="promo" data-discount-apply @if ($applied_code) hidden @endif>
                                @csrf
                                <input type="text" name="code" placeholder="{{ __('site.cart.discount_placeholder') }}" autocomplete="off" maxlength="60" data-discount-input>
                                <button type="submit">{{ __('site.cart.discount_apply') }}</button>
                            </form>
                            <div class="applied" data-discount-chip @unless ($applied_code) hidden @endunless>
                                <span><span class="code" data-discount-code>{{ $applied_code }}</span>@if ($discount) · <span data-discount-name>{{ $discount->name }}</span>@endif</span>
                                <form method="post" action="/cart/discount" data-discount-remove>@csrf @method('DELETE')<button type="submit">{{ __('site.cart.discount_remove') }}</button></form>
                            </div>
                            <p class="promo-msg" data-discount-msg hidden></p>
                        </div>
                        <div class="r"><span>{{ __('site.cart.subtotal') }}</span><span data-cart-subtotal>@money($subtotal)</span></div>
                        <div class="r"><span>{{ __('site.cart.shipping') }}</span><small>{{ __('site.cart.shipping_at_checkout') }}</small></div>
                        <div class="r discount" data-cart-discount-row @unless (! empty($discount) && $discountCents > 0) hidden @endunless>
                            <span data-cart-discount-name>{{ $discount->name ?? '' }}</span>
                            <span data-cart-discount-amount>@if (! empty($discount) && $discountCents > 0)−@money($discountCents)@endif</span>
                        </div>
                        <div class="tot"><span>{{ __('site.cart.total') }}</span><span data-cart-total>@money($grand)</span></div>
                        <a class="checkout-btn" href="/checkout">{{ __('site.cart.checkout') }}</a>
                        <div class="secure">// {{ __('site.checkout.secure_note') }}</div>
                    </aside>
                </div>
            @endif
        </div>
    </main>

    @include('storefront.partials.cart-behavior')
@endsection
