{{--
 | Sankevi — the cutting list.
 |
 | This yard does not sell over a counter. In enquiry mode ($store->isEnquiryFlow())
 | the customer is not filling a basket, they are writing a CUTTING LIST that the
 | yard will price and call back about — so the page is a ruled ledger, the column
 | heads are Position / Quantity / Estimate, and the tally in the margin is
 | explicitly an ESTIMATE that nobody is being charged. A merchant who runs this
 | theme on the normal payment flow gets the same ledger with the ordinary cart
 | wording; only the copy branches, never the mechanics.
 |
 | UNTOUCHED CONTRACTS (the inline JS + the shared kit depend on these):
 |   [data-cart-root] [data-num-anim] · per line [data-cart-line] [data-line-qty]
 |   [data-line-subtotal] · qty forms [data-cart-qty] + @csrf/@method('PATCH') +
 |   input[name=quantity][data-qty-value] + button[data-qty-step] · remove form
 |   [data-cart-remove] + @csrf/@method('DELETE') · discount region
 |   [data-cart-discount] [data-discount-apply|input|chip|code|name|remove|msg] ·
 |   totals [data-cart-subtotal] [data-cart-total] [data-cart-discount-row|name|amount]
--}}
@php
    $enquiry = $store->isEnquiryFlow();
    $title = $enquiry ? __('site.storefront.sankevi.req_panel_h') : __('site.cart.title');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $totalQty = $items->sum('quantity');
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal - $discountCents);

        // Copy switches on the flow, markup does not. Enquiry stores must never
        // read as a till; payment stores keep the platform's cart vocabulary.
        $crumbTxt     = $enquiry ? __('site.storefront.sankevi.req_crumb')     : __('site.cart.your_selection');
        $headingHtml  = $enquiry ? __('site.storefront.sankevi.req_h1_html')   : __('site.cart.heading_html');
        $leadTxt      = $enquiry
            ? __('site.storefront.sankevi.' . ($totalQty === 1 ? 'req_lead_one' : 'req_lead_many'), ['count' => $totalQty])
            : __('site.cart.' . ($totalQty === 1 ? 'item_count_one' : 'item_count_many'), ['count' => $totalQty]);
        $emptyHtml    = $enquiry ? __('site.storefront.sankevi.req_empty_h_html') : __('site.cart.empty_title');
        $emptyP       = $enquiry ? __('site.storefront.sankevi.req_empty_p')    : __('site.cart.empty_sub');
        $emptyCta     = $enquiry ? __('site.storefront.sankevi.req_empty_cta')  : __('site.cart.start_shopping');
        $colEst       = $enquiry ? __('site.storefront.sankevi.req_col_est')    : __('site.cart.total');
        $backTxt      = $enquiry ? __('site.storefront.sankevi.req_back')       : __('site.cart.continue_shopping');
        $panelH       = $enquiry ? __('site.storefront.sankevi.req_panel_h')    : __('site.cart.summary');
        $shipNote     = $enquiry ? __('site.storefront.sankevi.req_delivery_note') : __('site.cart.shipping_at_checkout');
        $totalLabel   = $enquiry ? __('site.storefront.sankevi.req_est_label')  : __('site.cart.total');
        $ctaLabel     = $enquiry ? __('site.storefront.sankevi.req_cta')        : __('site.cart.checkout');
        $footNote     = $enquiry ? __('site.storefront.sankevi.req_no_payment') : __('site.checkout.secure_note');

        // Boards are chosen by axis — Length × Width × Treatment — and a customer
        // proof-reading a cutting list has to SEE the dimensions they picked, not
        // a squashed variant label. One query for the whole list rather than one
        // per line; falls back to the variant label when the product has no axes.
        $variantIds = $items->map(fn ($r) => $r['variant']?->id)->filter()->values()->all();
        $dimsByVariant = [];
        if ($variantIds !== []) {
            $axisRows = \Illuminate\Support\Facades\DB::table('product_variant_option_values as pv')
                ->join('product_option_values as v', 'v.id', '=', 'pv.product_option_value_id')
                ->join('product_options as o', 'o.id', '=', 'v.product_option_id')
                ->whereIn('pv.product_variant_id', $variantIds)
                ->orderBy('o.sort_order')
                ->orderBy('o.id')
                ->get(['pv.product_variant_id as vid', 'o.name as axis', 'v.value as val']);
            foreach ($axisRows as $r) {
                $dimsByVariant[$r->vid][] = ['k' => $r->axis, 'v' => $r->val];
            }
        }
    @endphp

    <style>
        /* padding-bottom, NOT the shorthand: `padding: 0 0 84px` would zero the
           .wrap gutter and let the ledger run to the viewport edge, out of line
           with the header and the footer. */
        .req-wrap { padding-bottom: 84px; }
        .req { display: grid; grid-template-columns: 1fr 372px; gap: 58px; padding: 30px 0 0; align-items: start; }

        /* ===== nothing on the list yet ===== */
        .req-empty { position: relative; overflow: hidden; text-align: center; padding: 92px 28px; margin-top: 34px; border: 1px solid var(--line); background: var(--surface); }
        .req-empty h2 { font-family: var(--display); font-weight: 500; font-size: clamp(28px, 3.6vw, 42px); line-height: 1.06; margin-bottom: 12px; }
        .req-empty h2 em { font-style: normal; font-weight: 600; color: var(--accent-ink); }
        .req-empty p { color: var(--muted); max-width: 46ch; margin: 0 auto 30px; }

        /* ===== the ledger. Column heads set in the spec register, ruled off
           above the first line the way a yard docket is ruled. ===== */
        .ledger-head { display: grid; grid-template-columns: 86px minmax(0, 1fr) 132px 132px; gap: 22px; align-items: end; padding-bottom: 11px; border-bottom: 1px solid var(--line2); font-size: 10px; font-weight: 500; letter-spacing: .22em; text-transform: uppercase; color: var(--faint); }
        .ledger-head .c-qty { text-align: center; }
        .ledger-head .c-est { text-align: right; color: var(--accent-ink); }

        .lines { counter-reset: plate; }
        .line { display: grid; grid-template-columns: 86px minmax(0, 1fr) 132px 132px; gap: 22px; align-items: center; padding: 22px 0; border-bottom: 1px solid var(--line); counter-increment: plate; }
        .line .img { position: relative; width: 86px; aspect-ratio: 4 / 5; overflow: hidden; background: linear-gradient(150deg, var(--surface2), #191510); display: grid; place-items: center; }
        .line .img img { width: 100%; height: 100%; object-fit: cover; }
        .line .plate { display: block; font-size: 10px; font-weight: 500; letter-spacing: .2em; color: var(--accent-ink); margin-bottom: 7px; }
        .line .plate::after { content: var(--plate-label, "№ ") counter(plate, decimal-leading-zero); }
        .lines.no-sheet .line .plate { display: none; }
        .line .t { font-family: var(--display); font-weight: 500; font-size: 23px; line-height: 1.15; }
        .line .t a { transition: color .25s ease; }
        .line .t a:hover { color: var(--accent-ink); }

        /* the cut — the axes the customer chose, each one legible on its own.
           This is the difference between a basket and a cutting list. */
        .line .dims { display: flex; flex-wrap: wrap; gap: 0 18px; margin-top: 9px; }
        .line .dims .d { display: inline-flex; align-items: baseline; gap: 7px; padding: 4px 0; font-size: 13px; color: var(--txt); }
        .line .dims .d + .d { padding-left: 18px; border-left: 1px solid var(--line2); }
        .line .dims .d b { font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); }
        .line .m { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-top: 8px; }
        .line .unit { font-size: 10.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--faint); margin-top: 8px; }

        /* quantity — a hairline gauge, never a pill */
        .qty { display: inline-flex; align-items: stretch; border: 1px solid var(--line2); }
        .line .qty-cell { display: flex; justify-content: center; }
        .qty form { display: inline-flex; }
        .qty button { width: 34px; background: none; border: none; color: var(--muted); font-size: 15px; transition: color .2s ease, background-color .2s ease; }
        .qty button:hover { color: var(--accent-ink); background: var(--surface2); }
        .qty .n { width: 42px; display: grid; place-items: center; font-size: 13px; font-variant-numeric: tabular-nums; border-left: 1px solid var(--line); border-right: 1px solid var(--line); }

        .line .actions { text-align: right; }
        .line .pr { font-family: var(--display); font-weight: 500; font-size: 22px; font-variant-numeric: tabular-nums; }
        .line .rm { display: block; margin: 10px 0 0 auto; background: none; border: none; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); transition: color .2s ease; }
        .line .rm:hover { color: var(--accent-ink); }

        .req-actions { margin-top: 28px; }

        /* ===== the tally in the margin ===== */
        .req-sum { position: sticky; top: calc(var(--header-height) + 26px); background: var(--surface); border: 1px solid var(--line); padding: 30px 28px 32px; }
        .req-sum h3 { font-family: var(--display); font-weight: 500; font-size: 25px; line-height: 1; padding-bottom: 16px; margin-bottom: 22px; border-bottom: 1px solid var(--line2); }

        .promo-region { margin-bottom: 22px; }
        .req-sum .promo { display: flex; gap: 9px; }
        .req-sum .promo input { flex: 1; min-width: 0; background: var(--bg); border: 1px solid var(--line2); padding: 12px 14px; color: var(--txt); font-family: var(--body); font-size: 11.5px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; }
        .req-sum .promo input::placeholder { color: var(--faint); }
        .req-sum .promo input:focus { outline: none; border-color: var(--accent); }
        .req-sum .promo .btn { padding: 12px 16px; font-size: 10.5px; letter-spacing: .14em; }
        .req-sum .applied { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 11px 14px; background: var(--surface2); border: 1px solid var(--line); font-size: 11.5px; letter-spacing: .1em; }
        .req-sum .applied .code { font-weight: 600; text-transform: uppercase; color: var(--accent-ink); }
        .req-sum .applied form button { background: none; border: none; font-size: 10px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); text-decoration: underline; }
        .req-sum .applied form button:hover { color: var(--accent-ink); }
        .promo-msg { margin-top: 9px; font-size: 11.5px; color: var(--muted); }
        /* The `hidden` attribute toggles the promo form / applied chip / discount
           row, but a class selector out-specifies the UA [hidden] rule — without
           this the empty chip leaks through. Scoped so it can't reach the
           checkout wizard. */
        .req-sum [hidden] { display: none !important; }

        .req-sum .r { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 13px; font-size: 13.5px; color: var(--muted); }
        .req-sum .r > span:first-child { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; }
        .req-sum .r > span:last-child { font-variant-numeric: tabular-nums; color: var(--txt); }
        .req-sum .r small { font-size: 10.5px; font-weight: 500; letter-spacing: .14em; text-transform: uppercase; color: var(--faint); }
        .req-sum .r.discount > span { color: var(--accent-ink); }
        .req-sum .tot { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-top: 18px; margin: 18px 0 0; border-top: 1px solid var(--line2); font-family: var(--display); font-weight: 500; font-size: 20px; }
        .req-sum .tot .v { font-size: 26px; font-variant-numeric: tabular-nums; color: var(--accent-ink); }
        /* The tilde lives OUTSIDE [data-cart-total] on purpose: the animation
           engine overwrites that node's textContent wholesale. */
        .req-sum .tot .approx { margin-right: 4px; font-size: 20px; color: var(--faint); }
        .req-sum .est-note { margin: 12px 0 22px; padding-left: 13px; border-left: 1px solid var(--line2); font-size: 12.5px; line-height: 1.6; color: var(--muted); }
        .req-sum .secure { margin-top: 18px; text-align: center; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .req-sum .btn.block { margin-top: 22px; }
        .req-sum .est-note + .btn.block { margin-top: 0; }

        @media (max-width: 980px) {
            .req { grid-template-columns: 1fr; gap: 34px; }
            .req-sum { position: static; }
        }
        @media (max-width: 700px) {
            .ledger-head { display: none; }
            .req-sum { order: -1; }
            .line { grid-template-columns: 74px minmax(0, 1fr); gap: 16px; padding: 20px 0; }
            .line .img { width: 74px; }
            .line .t { font-size: 19px; }
            .line .dims .d + .d { padding-left: 0; border-left: none; }
            .line .dims { gap: 0 16px; }
            .line .qty-cell { grid-column: 1 / -1; justify-content: flex-start; }
            .line .actions { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; margin-top: -6px; }
            .line .rm { margin: 0; }
        }
    </style>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap req-wrap">
            <div class="page-head reveal">
                @if ($theme->on('gutter_index'))
                    <span class="gx" aria-hidden="true" style="top: 60px;">{{ __('site.storefront.sankevi.req_gx') }}</span>
                @endif
                <div class="crumb">{{ $crumbTxt }}</div>
                <h1>{!! $headingHtml !!}</h1>
                @if ($items->isNotEmpty())
                    <p>{{ $leadTxt }}</p>
                @endif
            </div>

            @if ($items->isEmpty())
                <div class="req-empty cut cut-lg reveal">
                    <h2>{!! $emptyHtml !!}</h2>
                    <p>{{ $emptyP }}</p>
                    <a class="btn" href="/shop">{{ $emptyCta }}</a>
                </div>
            @else
                <div class="req" data-cart-root data-num-anim="{{ $store->numberAnimation() }}">
                    <div class="reveal">
                        <div class="ledger-head" aria-hidden="true">
                            <span></span>
                            <span>{{ __('site.storefront.sankevi.req_col_item') }}</span>
                            <span class="c-qty">{{ __('site.storefront.sankevi.req_col_qty') }}</span>
                            <span class="c-est">{{ $colEst }}</span>
                        </div>
                        <div class="lines {{ $theme->on('sheet_marks') ? '' : 'no-sheet' }}" style="--plate-label: '{{ str_replace(['\\', '\''], '', $theme->label('sheet_marks')) }} '">
                            @foreach ($items as $row)
                                @php
                                    $product = $row['product'];
                                    $variant = $row['variant'] ?? null;
                                    $qty = $row['quantity'];
                                    $lineId = $row['line_id'];
                                    $dims = $variant ? ($dimsByVariant[$variant->id] ?? []) : [];
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
                                        @if ($dims !== [])
                                            {{-- axes spelled out (Length 200 cm · Width 20 cm) --}}
                                            <div class="dims">
                                                @foreach ($dims as $d)
                                                    <span class="d"><b>{{ $d['k'] }}</b>{{ $d['v'] }}</span>
                                                @endforeach
                                            </div>
                                        @elseif ($variant)
                                            <div class="m">{{ $variant->label }}</div>
                                        @endif
                                        <div class="unit">{{ __('site.cart.unit_each', ['price' => \App\Services\Money::display($row['unit_price_cents'], $displayRate ?? 1.0, $displayCurrency ?? $store->currency)]) }}</div>
                                    </div>
                                    <div class="qty-cell">
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
                        <div class="req-actions">
                            <a class="btn outline" href="/shop">← {{ $backTxt }}</a>
                        </div>
                    </div>

                    <aside class="req-sum cut reveal s1">
                        <h3>{{ $panelH }}</h3>
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
                            <span><small>{{ $shipNote }}</small></span>
                        </div>
                        <div class="r discount" data-cart-discount-row @unless (! empty($discount) && $discountCents > 0) hidden @endunless>
                            <span data-cart-discount-name>{{ $discount->name ?? '' }}</span>
                            <span data-cart-discount-amount>@if (! empty($discount) && $discountCents > 0)−@money($discountCents)@endif</span>
                        </div>
                        <div class="tot">
                            <span>{{ $totalLabel }}</span>
                            <span class="v">@if ($enquiry)<span class="approx" aria-hidden="true">≈</span>@endif<span data-cart-total>@money($grand)</span></span>
                        </div>
                        @if ($enquiry)
                            {{-- Said plainly, next to the number, so nobody reads
                                 the tally as a bill. --}}
                            <p class="est-note">{{ __('site.storefront.sankevi.req_est_note') }}</p>
                        @endif
                        <a class="btn block" href="/checkout">{{ $ctaLabel }}</a>
                        <div class="secure">{{ $footNote }}</div>
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
