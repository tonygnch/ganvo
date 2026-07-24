{{--
 | Timber — cart. The cutting list on the counter: every line a docket entry
 | stamped with its lot number, quantities stepped in square mono chips, and
 | the totals plate clipped to the right rail before the load leaves the yard.
--}}
@php
    $title = __('site.cart.title');
@endphp
@extends('themes.timber.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal - $discountCents);
    @endphp

    <style>
        /* ===== Cart — the cutting list: ruled dockets on the left, the
           totals plate on the right. Light surfaces, hard 2px shadows. ===== */
        .cart-wrap { padding: 30px 0 70px; }

        .cart-empty { position: relative; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 2px 0 0 var(--line); text-align: center; padding: 70px 28px; margin-top: 26px; }
        .cart-empty .ring.e1 { width: 180px; height: 180px; right: -58px; bottom: -66px; opacity: .5; }
        .cart-empty .ring.e2 { width: 88px; height: 88px; right: 96px; bottom: 26px; opacity: .35; }
        .cart-empty h2 { position: relative; z-index: 1; font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: clamp(28px, 3.4vw, 38px); line-height: 1.05; margin-bottom: 10px; }
        .cart-empty h2 em { font-style: normal; color: var(--accent-deep); }
        .cart-empty p { position: relative; z-index: 1; color: var(--muted); margin-bottom: 26px; }

        .cart { display: grid; grid-template-columns: 1fr 380px; gap: 50px; padding: 20px 0 0; align-items: start; }

        /* ===== A docket line — one entry on the cutting list. The lot stamp
           is counted per line, exactly like the racks grid. ===== */
        .lines { counter-reset: lot; }
        .line { display: grid; grid-template-columns: 100px 1fr auto; gap: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 18px; margin-bottom: 16px; align-items: center; box-shadow: 0 2px 0 0 var(--line); counter-increment: lot; transition: border-color .25s ease, box-shadow .25s ease; }
        .line:hover { border-color: var(--line2); box-shadow: 0 2px 0 0 var(--line2); }
        .line .img { position: relative; height: 100px; width: 100px; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; flex-shrink: 0; }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .img .ph { display: grid; place-items: center; border: none; }
        .lines .line .img::after { content: var(--lot-label, "LOT ") counter(lot, decimal-leading-zero); position: absolute; left: 0; right: 0; bottom: 0; z-index: 2; text-align: center; font-family: var(--mono); font-size: 9px; letter-spacing: .12em; color: var(--faint); background: color-mix(in srgb, var(--surface) 86%, transparent); border-top: 1px solid var(--line); padding: 2px 0; }
        .lines.no-lot .line .img::after { display: none; }

        .line .t { font-family: var(--display); font-weight: 600; text-transform: uppercase; letter-spacing: .02em; font-size: 20px; line-height: 1.15; }
        .line .t a { transition: color .2s ease; }
        .line .t a:hover { color: var(--accent-deep); }
        .line .m { font-family: var(--mono); font-size: 11.5px; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin-top: 3px; }
        .line .unit { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); margin-top: 4px; }

        /* quantity stepper — a square gauge, never a pill */
        .qty { display: inline-flex; border: 1px solid var(--line2); border-radius: 6px; margin-top: 10px; overflow: hidden; box-shadow: 0 2px 0 0 var(--line); }
        .qty form { display: inline-flex; }
        .qty button { width: 32px; height: 32px; background: none; border: none; font-size: 15px; color: var(--txt); transition: background-color .2s ease, color .2s ease; }
        .qty button:hover { background: var(--surface2); color: var(--accent-deep); }
        .qty .n { width: 36px; display: grid; place-items: center; font-family: var(--mono); font-size: 13px; font-variant-numeric: tabular-nums; border-left: 1px solid var(--line); border-right: 1px solid var(--line); }

        .line .actions { text-align: right; }
        .line .pr { font-family: var(--display); font-weight: 700; font-size: 22px; font-variant-numeric: tabular-nums; text-align: right; color: var(--txt); }
        .line .rm { font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); background: none; border: none; display: block; margin-top: 10px; margin-left: auto; transition: color .2s ease; }
        .line .rm:hover { color: var(--accent-deep); text-decoration: underline; }

        .cart-actions { margin-top: 20px; }

        /* ===== The totals plate — the yard's tally, clipped to the rail. ===== */
        .summary { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 28px 26px 30px; position: sticky; top: calc(var(--header-height) + 24px); box-shadow: 0 2px 0 0 var(--line); }
        .summary h3 { font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .01em; font-size: 24px; line-height: 1; border-bottom: 2px solid var(--txt); padding-bottom: 12px; }
        .summary .rule-ticks { margin-bottom: 20px; }
        .summary .no-rule { height: 20px; }

        .promo-region { margin-bottom: 18px; }
        .summary .promo { display: flex; gap: 8px; }
        .summary .promo input { flex: 1; border: 1px solid var(--line2); border-radius: 6px; padding: 11px 14px; font-family: var(--mono); font-size: 12px; letter-spacing: .06em; text-transform: uppercase; background: var(--bg); color: var(--txt); min-width: 0; }
        .summary .promo input::placeholder { color: var(--faint); }
        .summary .promo input:focus { outline: none; border-color: var(--accent); }
        .summary .promo .btn { padding: 11px 16px; font-size: 14px; }
        .summary .applied { padding: 10px 14px; background: var(--surface2); border: 1px solid var(--line); border-radius: 6px; font-family: var(--mono); font-size: 12px; letter-spacing: .04em; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .summary .applied .code { font-weight: 600; text-transform: uppercase; color: var(--accent-deep); }
        .summary .applied form button { background: none; border: none; font-family: var(--mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); text-decoration: underline; }
        .summary .applied form button:hover { color: var(--accent-deep); }
        .promo-msg { margin-top: 8px; font-family: var(--mono); font-size: 11px; letter-spacing: .04em; color: var(--muted); }
        /* The `hidden` attribute toggles the promo form / applied chip / discount
           row, but a class selector (.summary .applied) out-specifies the UA
           [hidden] rule — so without this the empty chip + REMOVE link leak
           through. Scoped to .summary so it can't touch the checkout wizard. */
        .summary [hidden] { display: none !important; }

        .summary .r { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; margin-bottom: 12px; color: var(--muted); }
        .summary .r > span:first-child { font-family: var(--mono); font-size: 11.5px; letter-spacing: .08em; text-transform: uppercase; }
        .summary .r > span:last-child { font-variant-numeric: tabular-nums; color: var(--txt); }
        .summary .r small { font-family: var(--mono); font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }
        .summary .r.discount > span { color: var(--accent-deep); }
        .summary .tot { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-family: var(--display); font-weight: 700; text-transform: uppercase; letter-spacing: .02em; font-size: 20px; color: var(--txt); border-top: 2px solid var(--txt); padding-top: 14px; margin: 14px 0 20px; }
        .summary .tot span:last-child { font-size: 24px; font-variant-numeric: tabular-nums; color: var(--accent-deep); }
        .summary .secure { margin-top: 16px; text-align: center; font-family: var(--mono); font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--faint); }
        .summary .secure::before { content: "▮ "; color: var(--accent); }

        @media (prefers-reduced-motion: reduce) {
            .line, .line:hover, .qty button, .line .rm, .line .t a { transition: none; }
        }
        @media (max-width: 900px) {
            .cart { grid-template-columns: 1fr; gap: 30px; }
            .summary { position: static; }
        }
        @media (max-width: 540px) {
            .summary { order: -1; margin-bottom: 24px; }
            .line { grid-template-columns: 80px 1fr; }
            .lines .line .img::after { display: none; }
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
                    @if ($theme->on('grain_rings'))
                        <div class="ring e1" aria-hidden="true"></div>
                        <div class="ring e2" aria-hidden="true"></div>
                    @endif
                    <h2>{!! __('site.cart.empty_title') !!}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn" href="/">{{ __('site.cart.start_shopping') }}</a>
                </div>
            @else
                <div class="cart" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div class="reveal">
                        <div class="lines {{ $theme->on('lot_stamps') ? '' : 'no-lot' }}" style="--lot-label: '{{ str_replace(['\\', '\''], '', $theme->label('lot_stamps')) }} '">
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
                                            {{-- scaled plank stack, so an imageless line still
                                                 reads as timber rather than a blank tile --}}
                                            <div class="ph" style="width:100%;height:100%;display:grid;place-items:center">
                                                <span class="plank-mark sm" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                                            </div>
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
                        @if ($theme->on('ruler'))
                            <div class="rule-ticks" aria-hidden="true"></div>
                        @else
                            <div class="no-rule" aria-hidden="true"></div>
                        @endif
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
