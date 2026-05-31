@php
    $title = __('site.cart.title');
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        // Cart math — mirror what the shared cart computes so the summary
        // shows the same numbers as before.
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        // Shipping isn't known until address is entered — surface a friendly
        // "calculated at checkout" hint. The /checkout page does the real
        // shipping math + free-threshold logic.
        $grand = max(0, $subtotal - $discountCents);
    @endphp

    <style>
        /* ===== CART ===== */
        .cart-wrap { padding: 40px 0 80px; }
        .cart-head { margin-bottom: 28px; }
        .cart-head h1 {
            font-family: var(--display);
            font-size: clamp(36px, 4.5vw, 56px);
            font-weight: 500;
            line-height: 1;
        }
        .cart-head p {
            color: var(--muted);
            margin-top: 8px;
            font-size: 14px;
            letter-spacing: .02em;
        }

        .cart-empty {
            text-align: center;
            padding: 80px 24px;
            border: 1px solid var(--line);
        }
        .cart-empty h2 {
            font-family: var(--display);
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .cart-empty p { color: var(--muted); margin-bottom: 24px; font-size: 14px; }

        .cart {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 60px;
            align-items: start;
        }

        /* ----- line items ----- */
        .lines { display: flex; flex-direction: column; }
        .line {
            display: grid;
            grid-template-columns: 96px 1fr auto;
            gap: 20px;
            padding: 24px 0;
            border-bottom: 1px solid var(--line);
        }
        .line .img {
            height: 120px;
            background: var(--soft);
            overflow: hidden;
            display: grid;
            place-items: center;
            color: var(--muted);
            font-size: 10px;
            letter-spacing: .14em;
            text-transform: uppercase;
        }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .t {
            font-size: 16px;
            font-weight: 500;
            line-height: 1.3;
        }
        .line .t a { color: var(--ink); transition: color .15s ease; }
        .line .t a:hover { color: var(--accent); }
        .line .m { font-size: 13px; color: var(--muted); margin-top: 4px; }
        .line .unit { font-size: 12px; color: var(--muted); margin-top: 4px; letter-spacing: .02em; }

        .qty {
            display: inline-flex;
            border: 1px solid var(--line);
            margin-top: 14px;
        }
        .qty form { display: inline-flex; }
        .qty button {
            width: 32px;
            height: 32px;
            background: none;
            border: none;
            font-size: 15px;
            color: var(--ink);
            cursor: pointer;
            transition: background-color .12s ease;
        }
        .qty button:hover { background: var(--soft); }
        .qty .n {
            width: 38px;
            display: grid;
            place-items: center;
            font-size: 13px;
            border-left: 1px solid var(--line);
            border-right: 1px solid var(--line);
        }

        .line .pr {
            font-family: var(--display);
            font-size: 20px;
            text-align: right;
        }
        .line .rm {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 14px;
            background: none;
            border: none;
            cursor: pointer;
            text-align: right;
            width: 100%;
            transition: color .15s ease;
        }
        .line .rm:hover { color: var(--accent); }

        .cart-actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
        }

        /* ----- summary aside ----- */
        .summary {
            border: 1px solid var(--line);
            padding: 32px;
            position: sticky;
            top: 100px;
        }
        .summary h3 {
            font-family: var(--display);
            font-size: 26px;
            font-weight: 500;
            margin-bottom: 22px;
        }

        /* discount-form override — make it match Atelier paper/ink */
        .summary .promo {
            display: flex;
            gap: 0;
            margin-bottom: 22px;
        }
        .summary .promo input {
            flex: 1;
            border: 1px solid var(--line);
            border-right: none;
            padding: 12px 14px;
            font-family: var(--body);
            font-size: 13px;
            background: var(--paper);
            color: var(--ink);
            outline: none;
            min-width: 0;
        }
        .summary .promo input:focus { border-color: var(--ink); }
        .summary .promo button {
            border: 1px solid var(--ink);
            background: var(--ink);
            color: var(--paper);
            padding: 0 18px;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .15s ease;
        }
        .summary .promo button:hover { background: var(--accent); border-color: var(--accent); }

        .summary .applied {
            margin-bottom: 18px;
            padding: 10px 12px;
            background: var(--soft);
            border-left: 2px solid var(--accent);
            font-size: 12px;
            letter-spacing: .04em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary .applied form button {
            background: none;
            border: none;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            cursor: pointer;
            transition: color .15s ease;
        }
        .summary .applied form button:hover { color: var(--accent); }
        .summary .applied .code { font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .promo-region { margin-bottom: 22px; }
        .promo-msg {
            margin-top: 8px;
            font-size: 12px;
            letter-spacing: .02em;
            color: var(--muted);
        }

        .summary .r {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 14px;
            color: #4f4a40;
        }
        .summary .r.discount { color: var(--accent); }
        .summary .r small { color: var(--muted); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; }

        .summary .tot {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 600;
            border-top: 1px solid var(--line);
            padding-top: 18px;
            margin-top: 8px;
            margin-bottom: 24px;
        }
        .summary .checkout-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 16px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease;
            font-family: var(--body);
        }
        .summary .checkout-btn:hover { background: var(--accent); border-color: var(--accent); }

        .summary .secure {
            margin-top: 18px;
            text-align: center;
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        @media (max-width: 980px) {
            .cart { grid-template-columns: 1fr; gap: 40px; }
            .summary { position: static; }
        }
        @media (max-width: 540px) {
            .line { grid-template-columns: 80px 1fr; gap: 14px; }
            .line .img { height: 100px; }
            .line .actions { grid-column: 1 / -1; margin-top: 12px; display: flex; justify-content: space-between; align-items: center; }
            .line .pr { text-align: left; }
        }
    </style>

    {{-- Shared number-change animation engine (count / odometer / flip / fade
         / none — per the merchant's Storefront effects setting). Exposes
         window.ganvoAnimateNumber, used below for the rolling totals. --}}
    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap cart-wrap">
            <div class="cart-head rv">
                <h1>{{ __('site.cart.title') }}</h1>
                @if ($items->isNotEmpty())
                    <p>{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</p>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="cart-empty rv">
                    <h2>{{ __('site.cart.empty_title') }}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn outline" href="/">{{ __('site.cart.start_shopping') }}</a>
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
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="">
                                        @else
                                            <span>img</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="t"><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></div>
                                        @if ($variant)
                                            <div class="m">{{ $variant->label }}</div>
                                        @endif
                                        <div class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($row['unit_price_cents'], $displayRate ?? 1.0, $displayCurrency ?? $store->currency)]) }}</div>
                                        <div class="qty" aria-label="{{ __('site.cart.quantity_label') }}">
                                            <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ max(0, $qty - 1) }}" data-qty-value>
                                                <button type="submit" aria-label="{{ __('site.cart.decrease') }}" data-qty-step="-1">−</button>
                                            </form>
                                            <span class="n" data-line-qty>{{ $qty }}</span>
                                            <form method="post" action="/cart/{{ $lineId }}" data-cart-qty>
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="quantity" value="{{ $qty + 1 }}" data-qty-value>
                                                <button type="submit" aria-label="{{ __('site.cart.increase') }}" data-qty-step="1">+</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="actions" style="text-align:right">
                                        <div class="pr" data-line-subtotal>@money($row['subtotal_cents'])</div>
                                        <form method="post" action="/cart/{{ $lineId }}" style="display:block" data-cart-remove>
                                            @csrf
                                            @method('DELETE')
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

                    <aside class="summary rv">
                        <h3>{{ __('site.cart.summary') }}</h3>

                        {{-- Inline discount form (Atelier-styled, with AJAX hooks).
                             Mirrors the shared discount-form partial's behavior but
                             gives us markup we control for the async re-render. --}}
                        <div class="promo-region" data-cart-discount data-applied="{{ $applied_code ? '1' : '' }}">
                            <form method="post" action="/cart/discount" class="promo" data-discount-apply @if ($applied_code) hidden @endif>
                                @csrf
                                <input type="text" name="code" placeholder="{{ __('site.cart.discount_placeholder') }}"
                                       autocomplete="off" spellcheck="false" maxlength="60" data-discount-input>
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
                        <a class="checkout-btn" href="/checkout">{{ __('site.cart.checkout') }}</a>
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

            // POST a form's data to its action, return parsed JSON cart state.
            async function send(form, overrides) {
                var fd = new FormData(form);
                if (overrides) Object.keys(overrides).forEach(function (k) { fd.set(k, overrides[k]); });
                var res = await fetch(form.action, {
                    method: 'POST', // Laravel reads _method for PATCH/DELETE spoofing
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                if (! res.ok) throw new Error('Request failed: ' + res.status);
                return res.json();
            }

            // Number-change animation comes from the shared engine
            // (storefront/partials/number-anim). Falls back to a plain text
            // set if for any reason it is not present.
            var animate = window.ganvoAnimateNumber || function (el, str) { if (el) el.textContent = str; };

            // Update the header bag count + summary totals + each line subtotal.
            function applyState(s) {
                // Header cart count (in the layout's .bag .n) — rolls too.
                animate(document.querySelector('.bag .n'), String(s.item_count));

                // Summary totals — rolling count-up/down.
                animate(root.querySelector('[data-cart-subtotal]'), s.subtotal);
                animate(root.querySelector('[data-cart-total]'), s.total);

                // Discount summary row
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

                // Per-line subtotals + quantities
                (s.lines || []).forEach(function (line) {
                    var row = root.querySelector('[data-cart-line="' + line.line_id + '"]');
                    if (! row) return;
                    animate(row.querySelector('[data-line-subtotal]'), line.subtotal);
                    animate(row.querySelector('[data-line-qty]'), String(line.quantity));
                    // Keep the +/- hidden inputs in step with the new qty.
                    row.querySelectorAll('[data-qty-step]').forEach(function (btn) {
                        var step = parseInt(btn.getAttribute('data-qty-step'), 10);
                        var input = btn.closest('form').querySelector('[data-qty-value]');
                        if (input) input.value = Math.max(0, line.quantity + step);
                    });
                });

                // If the cart emptied out, reload to render the empty state
                // (simpler + guaranteed correct vs. reconstructing it here).
                if (s.empty) { window.location.reload(); }
            }

            // Fade + remove a line element, then collapse its height.
            function dropLine(lineId) {
                var row = root.querySelector('[data-cart-line="' + lineId + '"]');
                if (! row) return;
                row.style.transition = 'opacity .2s ease';
                row.style.opacity = '0';
                setTimeout(function () { row.remove(); }, 200);
            }

            // ----- quantity +/- -----
            root.querySelectorAll('[data-cart-qty]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    send(form).then(function (s) {
                        if (s.line_removed && s.line_id) dropLine(s.line_id);
                        applyState(s);
                    }).catch(function () { form.submit(); }); // fall back to full post
                });
            });

            // ----- remove line -----
            root.querySelectorAll('[data-cart-remove]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    send(form).then(function (s) {
                        if (s.line_id) dropLine(s.line_id);
                        applyState(s);
                    }).catch(function () { form.submit(); });
                });
            });

            // ----- discount apply / remove -----
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
