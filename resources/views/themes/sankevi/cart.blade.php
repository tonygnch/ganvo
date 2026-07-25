{{--
 | Sankevi — cart. The loading list: every line a ruled row with its plate
 | number, the quantity stepped in a hairline gauge, and the tally standing
 | in the right margin until the load is signed off.
--}}
@php
    $title = __('site.cart.title');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal - $discountCents);
    @endphp

    <style>
        .cart-wrap { padding: 0 0 80px; }
        .cart { display: grid; grid-template-columns: 1fr 360px; gap: 60px; padding: 34px 0 0; align-items: start; }

        .cart-empty { position: relative; overflow: hidden; text-align: center; padding: 92px 28px; margin-top: 34px; border: 1px solid var(--line); background: var(--surface); }
        .cart-empty h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.6vw, 42px); line-height: 1.06; margin-bottom: 12px; }
        .cart-empty h2 em { font-style: italic; color: var(--accent); }
        .cart-empty p { color: var(--muted); margin-bottom: 30px; }

        /* ===== one line of the loading list ===== */
        .lines { counter-reset: plate; border-top: 1px solid var(--line2); }
        .line { display: grid; grid-template-columns: 104px 1fr auto; gap: 24px; align-items: center; padding: 24px 0; border-bottom: 1px solid var(--line); counter-increment: plate; }
        .line .img { position: relative; width: 104px; aspect-ratio: 4 / 5; overflow: hidden; background: linear-gradient(150deg, var(--surface2), #191510); display: grid; place-items: center; }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .plate { display: block; font-size: 10px; font-weight: 500; letter-spacing: .2em; color: var(--accent); margin-bottom: 7px; }
        .line .plate::after { content: var(--plate-label, "№ ") counter(plate, decimal-leading-zero); }
        .lines.no-sheet .line .plate { display: none; }
        .line .t { font-family: var(--display); font-weight: 500; font-size: 23px; line-height: 1.15; }
        .line .t a { transition: color .25s ease; }
        .line .t a:hover { color: var(--accent); }
        .line .m { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-top: 6px; }
        .line .unit { font-size: 10.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--faint); margin-top: 4px; }

        /* quantity — a hairline gauge, never a pill */
        .qty { display: inline-flex; align-items: stretch; border: 1px solid var(--line2); margin-top: 14px; }
        .qty form { display: inline-flex; }
        .qty button { width: 34px; background: none; border: none; color: var(--muted); font-size: 15px; transition: color .2s ease, background-color .2s ease; }
        .qty button:hover { color: var(--accent); background: var(--surface2); }
        .qty .n { width: 40px; display: grid; place-items: center; font-size: 13px; font-variant-numeric: tabular-nums; border-left: 1px solid var(--line); border-right: 1px solid var(--line); }

        .line .actions { text-align: right; }
        .line .pr { font-family: var(--display); font-weight: 500; font-size: 22px; font-variant-numeric: tabular-nums; }
        .line .rm { display: block; margin: 12px 0 0 auto; background: none; border: none; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); transition: color .2s ease; }
        .line .rm:hover { color: var(--accent); }

        .cart-actions { margin-top: 30px; }

        /* ===== the tally ===== */
        .summary { position: sticky; top: calc(var(--header-height) + 26px); background: var(--surface); border: 1px solid var(--line); padding: 30px 28px 32px; }
        .summary h3 { font-family: var(--display); font-weight: 500; font-size: 25px; line-height: 1; padding-bottom: 16px; margin-bottom: 22px; border-bottom: 1px solid var(--line2); }

        .promo-region { margin-bottom: 22px; }
        .summary .promo { display: flex; gap: 9px; }
        .summary .promo input { flex: 1; min-width: 0; background: var(--bg); border: 1px solid var(--line2); padding: 12px 14px; color: var(--txt); font-family: var(--body); font-size: 11.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; }
        .summary .promo input::placeholder { color: var(--faint); }
        .summary .promo input:focus { outline: none; border-color: var(--accent); }
        .summary .promo .btn { padding: 12px 16px; font-size: 10.5px; letter-spacing: .14em; }
        .summary .applied { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 11px 14px; background: var(--surface2); border: 1px solid var(--line); font-size: 11.5px; letter-spacing: .1em; }
        .summary .applied .code { font-weight: 600; text-transform: uppercase; color: var(--accent); }
        .summary .applied form button { background: none; border: none; font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); text-decoration: underline; }
        .summary .applied form button:hover { color: var(--accent); }
        .promo-msg { margin-top: 9px; font-size: 11.5px; color: var(--muted); }
        /* The `hidden` attribute toggles the promo form / applied chip / discount
           row, but a class selector out-specifies the UA [hidden] rule — without
           this the empty chip leaks through. Scoped so it can't reach the
           checkout wizard. */
        .summary [hidden] { display: none !important; }

        .summary .r { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 13px; font-size: 13.5px; color: var(--muted); }
        .summary .r > span:first-child { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; }
        .summary .r > span:last-child { font-variant-numeric: tabular-nums; color: var(--txt); }
        .summary .r small { font-size: 10.5px; font-weight: 500; letter-spacing: .14em; text-transform: uppercase; color: var(--faint); }
        .summary .r.discount > span { color: var(--accent); }
        .summary .tot { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-top: 18px; margin: 18px 0 22px; border-top: 1px solid var(--line2); font-family: var(--display); font-weight: 500; font-size: 20px; }
        .summary .tot span:last-child { font-size: 26px; font-variant-numeric: tabular-nums; color: var(--accent); }
        .summary .secure { margin-top: 18px; text-align: center; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }

        @media (max-width: 900px) {
            .cart { grid-template-columns: 1fr; gap: 34px; }
            .summary { position: static; }
        }
        @media (max-width: 560px) {
            .summary { order: -1; }
            .line { grid-template-columns: 74px 1fr; gap: 16px; padding: 20px 0; }
            .line .img { width: 74px; }
            .line .t { font-size: 19px; }
            .line .actions { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
            .line .rm { margin: 0; }
        }
    </style>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap cart-wrap">
            <div class="page-head reveal">
                <div class="crumb">{{ __('site.cart.your_selection') }}</div>
                <h1>{!! __('site.cart.heading_html') !!}</h1>
                @if ($items->isNotEmpty())
                    <p>{{ __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]) }}</p>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="cart-empty cut cut-lg reveal">
                    <h2>{!! __('site.cart.empty_title') !!}</h2>
                    <p>{{ __('site.cart.empty_sub') }}</p>
                    <a class="btn" href="/">{{ __('site.cart.start_shopping') }}</a>
                </div>
            @else
                <div class="cart" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div class="reveal">
                        <div class="lines {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--plate-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                            @foreach ($items as $row)
                                @php
                                    $product = $row['product'];
                                    $variant = $row['variant'] ?? null;
                                    $qty = $row['quantity'];
                                    $lineId = $row['line_id'];
                                @endphp
                                <div class="line" data-cart-line="{{ $lineId }}">
                                    <div class="img cut cut-sm">
                                        @if ($product->image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}@if ($variant) — {{ $variant->label }}@endif">
                                        @else
                                            {{-- the board glyph, scaled, so an imageless line still
                                                 reads as timber and not as a hole in the list --}}
                                            <span class="board-glyph sm" aria-hidden="true"><i></i></span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="plate" aria-hidden="true"></span>
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

                    <aside class="summary cut reveal s1">
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
