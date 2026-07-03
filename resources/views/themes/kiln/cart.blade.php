@php
    $title = __('site.cart.title');
@endphp
@extends('themes.kiln.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal - $discountCents);
    @endphp

    <style>
        .cart-wrap { padding: 30px 0 70px; }

        .cart-empty { background: var(--card); border: 1px solid var(--line); text-align: center; padding: 70px 28px; margin-top: 26px; position: relative; }
        .cart-empty .rings-mark { margin: 0 auto 22px; }
        .cart-empty h2 { font-family: var(--serif); font-size: 30px; font-weight: 400; margin-bottom: 10px; }
        .cart-empty h2 em { font-style: italic; color: var(--accent); }
        .cart-empty p { color: var(--muted); margin-bottom: 26px; }

        .cart { display: grid; grid-template-columns: 1fr 380px; gap: 60px; padding: 20px 0 0; align-items: start; }

        .lines { border-top: 1px solid var(--ink); }
        .line { display: grid; grid-template-columns: 96px 1fr auto; gap: 20px; padding: 24px 0; border-bottom: 1px solid var(--line); align-items: center; }
        .line .img { height: 96px; width: 96px; overflow: hidden; flex-shrink: 0; background: var(--stone); }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .img .ph, .line .img .bloomph { display: grid; place-items: center; }
        .line .t { font-family: var(--serif); font-size: 20px; line-height: 1.2; }
        .line .t a:hover { color: var(--accent); }
        .line .m { font-size: 13px; color: var(--muted); margin-top: 2px; }
        .line .unit { font-size: 12px; color: var(--muted); margin-top: 4px; }

        .qty { display: inline-flex; border: 1px solid var(--line); margin-top: 12px; overflow: hidden; }
        .qty form { display: inline-flex; }
        .qty button { width: 32px; height: 32px; background: none; border: none; font-size: 15px; color: var(--ink); transition: background-color .2s ease; }
        .qty button:hover { background: var(--soft); }
        .qty .n { width: 38px; display: grid; place-items: center; font-size: 13px; border-left: 1px solid var(--line); border-right: 1px solid var(--line); }

        .line .actions { text-align: right; }
        .line .pr { font-family: var(--display); font-weight: 600; font-size: 17px; font-variant-numeric: tabular-nums; text-align: right; color: var(--ink); }
        .line .rm { font-family: var(--display); font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); background: none; border: none; display: block; margin-top: 12px; margin-left: auto; transition: color .2s ease; }
        .line .rm:hover { color: var(--ink); }

        .cart-actions { margin-top: 26px; }

        .summary { background: none; border: 1px solid var(--ink); padding: 32px; position: sticky; top: 100px; }
        .summary h3 { font-family: var(--serif); font-size: 24px; font-weight: 400; margin-bottom: 22px; }

        .promo-region { margin-bottom: 22px; }
        .summary .promo { display: flex; border: 1px solid var(--ink); }
        .summary .promo input { flex: 1; border: none; padding: 12px; font-family: inherit; font-size: 13px; background: var(--card); color: var(--ink); min-width: 0; }
        .summary .promo input:focus { outline: none; }
        .summary .promo .btn { padding: 0 16px; font-size: 10px; }
        .summary .applied { padding: 11px 16px; background: var(--soft); border: 1px solid var(--line); font-size: 13px; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .summary .applied .code { font-weight: 600; color: var(--accent); }
        .summary .applied form button { background: none; border: none; font-family: var(--display); font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); }
        .summary .applied form button:hover { color: var(--ink); }
        .promo-msg { margin-top: 8px; font-size: 12px; color: var(--muted); }
        /* The `hidden` attribute toggles the promo form / applied chip / discount
           row, but a class selector (.summary .applied) out-specifies the UA
           [hidden] rule — so without this the empty chip + REMOVE link leak
           through. Scoped to .summary so it can't touch the checkout wizard. */
        .summary [hidden] { display: none !important; }

        .summary .r { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 14px; color: #5d5c50; }
        .summary .r small { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .summary .r.discount { color: var(--accent); }
        .summary .tot { display: flex; justify-content: space-between; font-size: 18px; font-weight: 600; color: var(--ink); border-top: 1px solid var(--ink); padding-top: 18px; margin: 8px 0 22px; }
        .summary .tot span:last-child { font-family: var(--body); font-variant-numeric: tabular-nums; color: var(--ink); }
        .summary .secure { margin-top: 16px; text-align: center; font-size: 12px; letter-spacing: .04em; color: var(--muted); }

        @media (max-width: 900px) {
            .cart { grid-template-columns: 1fr; gap: 30px; }
            .summary { position: static; }
        }
        @media (max-width: 540px) {
            .summary { order: -1; margin-bottom: 24px; }
            .line { grid-template-columns: 80px 1fr; }
            .line .actions { grid-column: 1 / -1; margin-top: 8px; display: flex; justify-content: space-between; align-items: center; }
            .line .pr { text-align: left; }
            .line .rm { margin-top: 0; }
        }
    </style>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap cart-wrap">
            <div class="page-head reveal" style="padding-top: 10px; padding-bottom: 10px;">
                <div class="crumb">{{ __('site.cart.your_selection') }}</div>
                <h1>{!! __('site.cart.heading_html') !!}</h1>
                @if ($items->isNotEmpty())
                    <p>{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</p>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="cart-empty reveal">
                    <div class="rings-mark" aria-hidden="true"></div>
                    <h2>{!! __('site.cart.empty_title') !!}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn" href="/">{{ __('site.cart.start_shopping') }}</a>
                </div>
            @else
                <div class="cart" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div class="reveal">
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
                                            <div class="ph" style="width:100%;height:100%"></div>
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
                                    <div class="actions">
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
                            <a class="btn outline" href="/">← {{ __('site.cart.continue_shopping') }}</a>
                        </div>
                    </div>

                    <aside class="summary reveal s1">
                        <h3>{{ __('site.cart.summary') }}</h3>
                        <div class="promo-region" data-cart-discount data-applied="{{ $applied_code ? '1' : '' }}">
                            <form method="post" action="/cart/discount" class="promo" data-discount-apply @if ($applied_code) hidden @endif>
                                @csrf
                                <input type="text" name="code" placeholder="{{ __('site.cart.discount_placeholder') }}" autocomplete="off" spellcheck="false" maxlength="60" data-discount-input aria-label="{{ __('site.cart.discount_placeholder') }}">
                                <button type="submit" class="btn">{{ __('site.cart.discount_apply') }}</button>
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
                        <a class="btn block" href="/checkout">{{ __('site.cart.checkout') }}</a>
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
                animate(document.querySelector('.bag .n'), s.item_count);
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
