@php
    $title = __('site.cart.title');
@endphp
@extends('themes.brick.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal - $discountCents);
    @endphp

    <style>
        .cart-wrap { padding: 0 0 70px; }
        .cart-empty { border: 2.5px solid var(--ink); box-shadow: var(--pop); text-align: center; padding: 70px 24px; margin-top: 32px; }
        .cart-empty h2 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: 28px; margin-bottom: 10px; }
        .cart-empty p { color: var(--text-muted); margin-bottom: 24px; }

        .cart { display: grid; grid-template-columns: 1fr 360px; gap: 28px; align-items: start; }
        .lines { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); }
        .line { display: grid; grid-template-columns: 96px 1fr auto; gap: 18px; padding: 20px; border-bottom: 2.5px solid var(--ink); }
        .line:last-child { border-bottom: none; }
        .line .img { height: 110px; border: 2.5px solid var(--ink); background: var(--soft); overflow: hidden; }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .t { font-family: var(--display); font-weight: 700; font-size: 16px; line-height: 1.2; }
        .line .t a:hover { background: var(--accent); }
        .line .m { font-size: 12px; color: var(--muted); margin-top: 4px; font-weight: 600; }
        .line .unit { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .qty { display: inline-flex; border: 2.5px solid var(--ink); margin-top: 12px; }
        .qty form { display: inline-flex; }
        .qty button { width: 44px; height: 44px; background: var(--paper); border: none; font-family: var(--display); font-weight: 800; font-size: 16px; transition: background-color .12s ease; }
        .qty button:hover { background: var(--accent); }
        .qty button:active { transform: translate(2px, 2px); }
        .qty .n { width: 44px; display: grid; place-items: center; font-family: var(--display); font-weight: 800; font-size: 14px; border-left: 2.5px solid var(--ink); border-right: 2.5px solid var(--ink); }
        .line .pr { font-family: var(--display); font-weight: 800; font-size: 18px; text-align: right; }
        .line .rm { font-family: var(--display); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-top: 12px; background: none; border: none; cursor: pointer; text-align: right; width: 100%; transition: background-color .12s ease, color .12s ease; }
        .line .rm:hover { color: var(--ink); background: var(--accent); }
        .line .rm:active { transform: translate(2px, 2px); }

        .cart-actions { margin-top: 24px; }

        .summary { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); padding: 26px; position: sticky; top: 90px; }
        .summary h3 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: 22px; margin-bottom: 20px; }
        .summary .promo { display: flex; margin-bottom: 20px; }
        .summary .promo input { flex: 1; border: 2.5px solid var(--ink); border-right: none; padding: 11px 12px; font-family: var(--body); font-size: 13px; background: #fff; min-width: 0; }
        .summary .promo input:focus { outline: 3px solid var(--accent); outline-offset: -3px; }
        .summary .promo button { border: 2.5px solid var(--ink); background: var(--ink); color: var(--paper); padding: 0 16px; font-family: var(--display); font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; transition: background-color .12s ease, color .12s ease; }
        .summary .promo button:hover { background: var(--accent); color: var(--ink); }
        .summary .promo button:active { transform: translate(2px, 2px); }
        .summary .applied { margin-bottom: 18px; padding: 10px 12px; background: var(--accent); border: 2.5px solid var(--ink); font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .summary .applied form button { background: none; border: none; font-family: var(--display); font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; text-decoration: underline; }
        .summary .applied .code { font-weight: 800; }
        .promo-region { margin-bottom: 20px; }
        .promo-msg { margin-top: 8px; font-size: 12px; color: var(--text-muted); }
        .summary .r { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 12px; font-weight: 600; }
        .summary .r.discount { color: var(--ink); }
        .summary .r small { color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 700; }
        .summary .tot { display: flex; justify-content: space-between; font-family: var(--display); font-size: 18px; font-weight: 900; border-top: 2.5px solid var(--ink); padding-top: 16px; margin: 8px 0 20px; text-transform: uppercase; }
        .summary .secure { margin-top: 16px; text-align: center; font-family: var(--display); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }

        @media (max-width: 980px) { .cart { grid-template-columns: 1fr; } .summary { position: static; } }
        @media (max-width: 540px) {
            /* Float the total + checkout above the line items so it's visible
               without scrolling past a long cart. */
            .summary { order: -1; margin-bottom: 24px; }
            .qty button { font-size: 18px; }
            .line { grid-template-columns: 80px 1fr; }
            .line .actions { grid-column: 1 / -1; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
            .line .pr { text-align: left; }
        }
        @media (prefers-reduced-motion: reduce) {
            .qty button:active, .line .rm:active, .summary .promo button:active { transform: none; }
        }
    </style>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap cart-wrap">
            <div class="ed-head rv">
                <div>
                    <div class="crumb">{{ __('site.cart.your_selection') }}</div>
                    <h1>{{ __('site.cart.title') }}</h1>
                </div>
                @if ($items->isNotEmpty())
                    <div class="meta">{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</div>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="cart-empty rv">
                    <h2>{{ __('site.cart.empty_title') }}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn accent" href="/">{{ __('site.cart.start_shopping') }}</a>
                </div>
            @else
                <div class="cart" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div class="rv">
                        <div class="lines">
                            @foreach ($items as $row)
                                @php
                                    $product = $row['product'];
                                    $variant = $row['variant'] ?? null;
                                    $qty = $row['quantity'];
                                    $lineId = $row['line_id'];
                                @endphp
                                <div class="line" data-cart-line="{{ $lineId }}">
                                    <div class="img">
                                        @if ($product->image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}@if ($variant) — {{ $variant->label }}@endif">
                                        @else
                                            <div class="ph" style="width:100%;height:100%"><span>img</span></div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="t"><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></div>
                                        @if ($variant)<div class="m">{{ $variant->label }}</div>@endif
                                        <div class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($row['unit_price_cents'], $displayRate ?? 1.0, $displayCurrency ?? $store->currency)]) }}</div>
                                        <div class="qty" aria-label="{{ __('site.cart.quantity_label') }}">
                                            <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ max(0, $qty - 1) }}" data-qty-value>
                                                <button type="submit" aria-label="{{ __('site.cart.decrease') }}" data-qty-step="-1">−</button>
                                            </form>
                                            <span class="n" data-line-qty>{{ $qty }}</span>
                                            <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $qty + 1 }}" data-qty-value>
                                                <button type="submit" aria-label="{{ __('site.cart.increase') }}" data-qty-step="1">+</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="actions" style="text-align:right">
                                        <div class="pr" data-line-subtotal>@money($row['subtotal_cents'])</div>
                                        <form method="post" action="/cart/{{ $lineId }}" style="display:block" data-cart-remove>
                                            @csrf @method('DELETE')
                                            <button type="submit" class="rm">{{ __('site.cart.remove') }}</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="cart-actions">
                            <a class="btn" href="/">← {{ __('site.cart.continue_shopping') }}</a>
                        </div>
                    </div>

                    <aside class="summary rv">
                        <h3>{{ __('site.cart.summary') }}</h3>
                        <div class="promo-region" data-cart-discount data-applied="{{ $applied_code ? '1' : '' }}">
                            <form method="post" action="/cart/discount" class="promo" data-discount-apply @if ($applied_code) hidden @endif>
                                @csrf
                                <input type="text" name="code" placeholder="{{ __('site.cart.discount_placeholder') }}" autocomplete="off" spellcheck="false" maxlength="60" data-discount-input>
                                <button type="submit">{{ __('site.cart.discount_apply') }}</button>
                            </form>
                            <div class="applied" data-discount-chip @unless ($applied_code) hidden @endunless>
                                <span><span class="code" data-discount-code>{{ $applied_code }}</span>@if ($discount) · <span data-discount-name>{{ $discount->name }}</span>@endif</span>
                                <form method="post" action="/cart/discount" data-discount-remove>
                                    @csrf @method('DELETE')
                                    <button type="submit">{{ __('site.cart.discount_remove') }}</button>
                                </form>
                            </div>
                            <p class="promo-msg" data-discount-msg hidden></p>
                        </div>

                        <div class="r">
                            <span>{{ __('site.cart.subtotal') }}</span>
                            <span data-cart-subtotal>@money($subtotal)</span>
                        </div>
                        <div class="r">
                            <span>{{ __('site.cart.shipping') }}</span>
                            <span><small>{{ __('site.cart.shipping_at_checkout') }}</small></span>
                        </div>
                        <div class="r discount" data-cart-discount-row @unless (! empty($discount) && $discountCents > 0) hidden @endunless>
                            <span data-cart-discount-name>{{ $discount->name ?? '' }}</span>
                            <span data-cart-discount-amount>@if (! empty($discount) && $discountCents > 0)−@money($discountCents)@endif</span>
                        </div>
                        <div class="tot">
                            <span>{{ __('site.cart.total') }}</span>
                            <span data-cart-total>@money($grand)</span>
                        </div>
                        <a class="btn accent block" href="/checkout">{{ __('site.cart.checkout') }}</a>
                        <div class="secure">{{ __('site.checkout.secure_note') }}</div>
                    </aside>
                </div>
            @endif
        </div>
    </main>

    <script>
        (function () {
            var root = document.querySelector('[data-cart-root]');
            if (! root) return;

            async function send(form, overrides) {
                var fd = new FormData(form);
                if (overrides) Object.keys(overrides).forEach(function (k) { fd.set(k, overrides[k]); });
                var res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                if (! res.ok) throw new Error('Request failed: ' + res.status);
                return res.json();
            }

            var animate = window.ganvoAnimateNumber || function (el, str) { if (el) el.textContent = str; };

            function applyState(s) {
                animate(document.querySelector('.bag .n'), '[' + s.item_count + ']');
                animate(root.querySelector('[data-cart-subtotal]'), s.subtotal);
                animate(root.querySelector('[data-cart-total]'), s.total);

                var dRow = root.querySelector('[data-cart-discount-row]');
                if (dRow) {
                    if (s.discount) {
                        dRow.querySelector('[data-cart-discount-name]').textContent = s.discount.name;
                        animate(dRow.querySelector('[data-cart-discount-amount]'), s.discount.amount);
                        dRow.hidden = false;
                    } else {
                        dRow.hidden = true;
                    }
                }

                (s.lines || []).forEach(function (line) {
                    var row = root.querySelector('[data-cart-line="' + line.line_id + '"]');
                    if (! row) return;
                    animate(row.querySelector('[data-line-subtotal]'), line.subtotal);
                    animate(row.querySelector('[data-line-qty]'), String(line.quantity));
                    row.querySelectorAll('[data-qty-step]').forEach(function (btn) {
                        var step = parseInt(btn.getAttribute('data-qty-step'), 10);
                        var input = btn.closest('form').querySelector('[data-qty-value]');
                        if (input) input.value = Math.max(0, line.quantity + step);
                    });
                });

                if (s.empty) { window.location.reload(); }
            }

            function dropLine(lineId) {
                var row = root.querySelector('[data-cart-line="' + lineId + '"]');
                if (! row) return;
                row.style.transition = 'opacity .2s ease';
                row.style.opacity = '0';
                setTimeout(function () { row.remove(); }, 200);
            }

            root.querySelectorAll('[data-cart-qty]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    send(form).then(function (s) {
                        if (s.line_removed && s.line_id) dropLine(s.line_id);
                        applyState(s);
                    }).catch(function () { form.submit(); });
                });
            });

            root.querySelectorAll('[data-cart-remove]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    send(form).then(function (s) {
                        if (s.line_id) dropLine(s.line_id);
                        applyState(s);
                    }).catch(function () { form.submit(); });
                });
            });

            var region = root.querySelector('[data-cart-discount]');
            if (region) {
                var applyForm  = region.querySelector('[data-discount-apply]');
                var chip       = region.querySelector('[data-discount-chip]');
                var msg        = region.querySelector('[data-discount-msg]');

                function renderDiscount(s) {
                    if (s.applied_code) {
                        region.querySelector('[data-discount-code]').textContent = s.applied_code;
                        var nameEl = region.querySelector('[data-discount-name]');
                        if (nameEl && s.discount) nameEl.textContent = s.discount.name;
                        applyForm.hidden = true;
                        chip.hidden = false;
                    } else {
                        applyForm.hidden = false;
                        chip.hidden = true;
                        var input = applyForm.querySelector('[data-discount-input]');
                        if (input) input.value = '';
                    }
                    if (msg) {
                        if (s.flash) { msg.textContent = s.flash; msg.hidden = false; }
                        else { msg.hidden = true; }
                    }
                }

                if (applyForm) {
                    applyForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        send(applyForm).then(function (s) { applyState(s); renderDiscount(s); })
                            .catch(function () { applyForm.submit(); });
                    });
                }
                var removeForm = region.querySelector('[data-discount-remove]');
                if (removeForm) {
                    removeForm.addEventListener('submit', function (e) {
                        e.preventDefault();
                        send(removeForm).then(function (s) { applyState(s); renderDiscount(s); })
                            .catch(function () { removeForm.submit(); });
                    });
                }
            }
        })();
    </script>
@endsection
