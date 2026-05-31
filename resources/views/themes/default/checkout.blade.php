@php
    $title = __('site.checkout.title');
@endphp
@extends('themes.default.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $shipping = $shipping_cents ?? 0;
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal + $shipping - $discountCents);

        $defaultAddress = $defaultAddress ?? [];
        $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG');
    @endphp

    <style>
        /* ===== CHECKOUT ===== */
        .co-wrap { padding: 40px 0 60px; }
        .co-head { margin-bottom: 28px; }
        .co-head h1 {
            font-family: var(--display);
            font-size: clamp(36px, 4.5vw, 56px);
            font-weight: 500;
            line-height: 1;
        }
        .co-head p {
            color: var(--muted);
            margin-top: 8px;
            font-size: 14px;
        }

        .checkout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 64px;
            align-items: start;
        }

        /* ----- section card (numbered) ----- */
        .fset {
            margin-bottom: 38px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--line);
        }
        .fset:last-of-type { border-bottom: 0; }
        .fset h3 {
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--ink);
            font-weight: 600;
        }
        .fset h3 .num {
            width: 26px;
            height: 26px;
            border: 1px solid var(--ink);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-family: var(--display);
            font-size: 14px;
            font-weight: 500;
            flex-shrink: 0;
            letter-spacing: 0;
        }

        .frow {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 7px;
            font-weight: 600;
        }
        .field label small {
            color: var(--muted);
            text-transform: none;
            letter-spacing: .02em;
            font-weight: 400;
            margin-left: 4px;
        }
        .field input,
        .field select,
        .field textarea {
            border: 1px solid var(--line);
            background: #fff;
            padding: 13px 14px;
            font-family: var(--body);
            font-size: 14px;
            color: var(--ink);
            border-radius: 0;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus { outline: none; border-color: var(--ink); }
        .field textarea { min-height: 90px; resize: vertical; }

        .co-signed-in {
            background: var(--soft);
            border-left: 2px solid var(--accent);
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            color: var(--ink);
        }
        .co-signed-in strong { font-weight: 600; }
        .co-signin-banner {
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 14px 18px;
            margin-bottom: 28px;
            font-size: 13px;
            color: var(--ink);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .co-signin-banner a {
            color: var(--accent);
            font-weight: 600;
            border-bottom: 1px solid currentColor;
            padding-bottom: 1px;
        }

        .errors {
            border: 1px solid #b91c1c;
            background: rgba(185, 28, 28, .04);
            padding: 14px 18px;
            margin-bottom: 28px;
            font-size: 13px;
            color: #b91c1c;
        }
        .errors ul { padding-left: 18px; }
        .errors li { margin: 2px 0; }

        .stub-notice {
            background: var(--soft);
            border-left: 2px solid var(--accent);
            padding: 14px 18px;
            font-size: 13px;
            color: var(--ink);
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .stub-notice .icon {
            display: inline-grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--accent);
            color: var(--paper);
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .stub-notice strong { display: block; margin-bottom: 4px; font-weight: 600; }

        .place-order {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 18px;
            font-size: 12px;
            letter-spacing: .2em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease;
            font-family: var(--body);
            margin-top: 24px;
        }
        .place-order:hover { background: var(--accent); border-color: var(--accent); }
        .place-order:disabled { opacity: .5; cursor: not-allowed; }

        /* ----- order summary aside ----- */
        .osum {
            border: 1px solid var(--line);
            padding: 30px;
            position: sticky;
            top: 100px;
        }
        .osum h2 {
            font-family: var(--display);
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 22px;
        }
        .osum .oitem {
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 14px;
            align-items: center;
            margin-bottom: 18px;
        }
        .osum .oitem .img {
            height: 72px;
            background: var(--soft);
            overflow: hidden;
            position: relative;
            display: grid;
            place-items: center;
            color: var(--muted);
            font-size: 9px;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .osum .oitem .img img { width: 100%; height: 100%; object-fit: cover; }
        .osum .oitem .qty-pill {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--ink);
            color: var(--paper);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 11px;
            display: grid;
            place-items: center;
            font-weight: 600;
        }
        .osum .oitem .nm { font-size: 13px; line-height: 1.3; }
        .osum .oitem .m { font-size: 12px; color: var(--muted); }
        .osum .oitem .pr { font-family: var(--display); font-size: 15px; }

        .osum .divider {
            border: 0;
            border-top: 1px solid var(--line);
            margin: 18px 0;
        }
        .osum .r {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin: 10px 0;
            color: #4f4a40;
        }
        .osum .r.discount { color: var(--accent); }
        .osum .tot {
            display: flex;
            justify-content: space-between;
            font-size: 19px;
            font-weight: 600;
            border-top: 1px solid var(--line);
            padding-top: 16px;
            margin: 14px 0 18px;
        }
        .osum .secure {
            font-size: 11px;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--muted);
            text-align: center;
            margin-top: 12px;
        }

        /* ===== WIZARD =====
           Progressive enhancement: without JS every step is visible and the
           single submit button at the end works as a normal long form. When
           the wizard JS boots it adds .wz-on to the form, which hides the
           non-current steps, reveals the stepper + per-step nav buttons. */

        /* Stepper (hidden until JS turns the wizard on) */
        .wz-steps { display: none; }
        .wz-on .wz-steps {
            display: flex;
            align-items: center;
            gap: 0;
            list-style: none;
            margin: 0 0 40px;
            padding: 0;
            counter-reset: wz;
        }
        .wz-steps li {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            font-size: 11px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            position: relative;
        }
        .wz-steps li:not(:last-child)::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--line);
            margin: 0 14px;
        }
        .wz-steps .dot {
            width: 30px; height: 30px;
            border: 1px solid var(--line);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-family: var(--display);
            font-size: 14px;
            color: var(--muted);
            flex-shrink: 0;
            transition: border-color .2s ease, background-color .2s ease, color .2s ease;
        }
        .wz-steps li.is-current { color: var(--ink); }
        .wz-steps li.is-current .dot { border-color: var(--ink); color: var(--ink); }
        .wz-steps li.is-done { color: var(--ink); cursor: pointer; }
        .wz-steps li.is-done .dot { border-color: var(--ink); background: var(--ink); color: var(--paper); }
        .wz-steps li.is-done .dot::after { content: "✓"; }
        .wz-steps li.is-done .dot .n { display: none; }
        .wz-steps .label { white-space: nowrap; }

        /* Steps */
        .wz-on .wz-step { display: none; }
        .wz-on .wz-step.is-current { display: block; animation: wzIn .35s ease; }
        @keyframes wzIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        /* Persistent action bar (always visible — holds the single submit
           button so no-JS users can still check out). */
        .wz-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--line);
        }
        .wz-back {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 12px;
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--body);
            transition: color .15s ease;
        }
        .wz-back:hover { color: var(--ink); }
        .pay-btn {
            margin-left: auto;
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            background: var(--ink);
            color: var(--paper);
            border: 1px solid var(--ink);
            padding: 16px 36px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--body);
            transition: background-color .2s ease, border-color .2s ease, opacity .2s ease;
        }
        .pay-btn:hover { background: var(--accent); border-color: var(--accent); }
        .pay-btn:disabled { opacity: .5; cursor: not-allowed; }

        @media (max-width: 980px) {
            .checkout { grid-template-columns: 1fr; gap: 40px; }
            .osum { position: static; order: -1; }
            .wz-steps .label { display: none; }
            .wz-on .wz-steps { margin-bottom: 28px; }
        }
        @media (max-width: 540px) {
            .frow { grid-template-columns: 1fr; }
        }
    </style>

    {{-- No-JS fallback: reveal every step (the inline `hidden` on steps 2–3
         avoids a flash-of-all-steps before the wizard JS boots) and drop the
         wizard-only chrome, so the page degrades to a single long form.
         (Real payments need JS regardless — the Stripe Payment Element is
         JS-driven — so this mainly covers stub-mode checkouts.) --}}
    <noscript>
        <style>
            .wz-step[hidden] { display: block !important; }
            .wz-steps { display: none !important; }
            .wz-back { display: none !important; }
        </style>
    </noscript>

    {{-- Shared number-change animation engine — drives the rolling/odometer
         total + shipping cost when the shipping method changes. --}}
    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap co-wrap">
            <div class="co-head rv">
                <h1>{{ __('site.checkout.title') }}</h1>
            </div>

            @guest('customer')
                @if ($store->showsAccountUi())
                    <div class="co-signin-banner rv">
                        <span>{{ __('site.checkout.have_account') }}</span>
                        <span>
                            <a href="/account/login?intent=checkout">{{ __('site.common.sign_in') }}</a>
                            @if ($store->allow_registration)
                                · <a href="/account/register">{{ __('site.checkout.create_account_link') }}</a>
                            @endif
                        </span>
                    </div>
                @endif
            @endguest

            @php
                $isStripe = ($payment_mode ?? 'stub') === 'stripe';
                // Button label: real payments say "Pay now", stub says "Place order".
                $payLabel = $isStripe ? __('site.checkout.pay_now') : __('site.checkout.action_place_order');
                $wizardSteps = [
                    1 => __('site.checkout.step_details'),
                    2 => __('site.checkout.step_delivery'),
                    3 => __('site.checkout.step_payment'),
                ];
            @endphp

            <form method="post" action="/checkout" data-wizard>
                @csrf

                @if ($errors->any())
                    <div class="errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Stepper (revealed only when the wizard JS boots). --}}
                <ol class="wz-steps" aria-hidden="true">
                    @foreach ($wizardSteps as $n => $label)
                        <li data-go-step="{{ $n }}" class="{{ $n === 1 ? 'is-current' : '' }}">
                            <span class="dot"><span class="n">{{ $n }}</span></span>
                            <span class="label">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="checkout">
                    <div class="co-main rv">
                        {{-- STEP 1 — Details: contact + shipping address --}}
                        <section class="wz-step is-current" data-step="1">
                            <div class="fset" style="border-bottom:0; padding-bottom:0; margin-bottom:24px;">
                                <h3>{{ __('site.checkout.sec_contact') }}</h3>
                                @if ($customer)
                                    <div class="co-signed-in">
                                        {!! __('site.checkout.signed_in_as', ['email' => '<strong>' . e($customer->email) . '</strong>']) !!}
                                    </div>
                                @endif
                                <div class="frow">
                                    <div class="field full">
                                        <label for="customer_email">{{ __('site.checkout.email') }}</label>
                                        <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', $customer?->email) }}" placeholder="you@example.com" required>
                                    </div>
                                    <div class="field full">
                                        <label for="customer_name">{{ __('site.checkout.full_name') }}</label>
                                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                                    </div>
                                    <div class="field full">
                                        <label for="customer_phone">{{ __('site.checkout.phone') }} <small>({{ __('site.common.optional') }})</small></label>
                                        <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="fset">
                                <h3>{{ __('site.checkout.sec_shipping') }}</h3>
                                <div class="frow">
                                    <div class="field full">
                                        <label for="address_line">{{ __('site.checkout.street') }}</label>
                                        <input type="text" name="address_line" id="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required>
                                    </div>
                                    <div class="field full">
                                        <label for="address_region">{{ __('site.checkout.region') }} <small>({{ __('site.common.optional') }})</small></label>
                                        <input type="text" name="address_region" id="address_region" value="{{ old('address_region', $defaultAddress['region'] ?? '') }}">
                                    </div>
                                    <div class="field">
                                        <label for="city">{{ __('site.checkout.city') }}</label>
                                        <input type="text" name="city" id="city" value="{{ old('city', $defaultAddress['city'] ?? '') }}" required>
                                    </div>
                                    <div class="field">
                                        <label for="postal_code">{{ __('site.checkout.postal') }}</label>
                                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $defaultAddress['postal_code'] ?? '') }}" required>
                                    </div>
                                    <div class="field full">
                                        <label for="country">{{ __('site.checkout.country') }}</label>
                                        <select name="country" id="country" required>
                                            @foreach ($countries as $code => $name)
                                                <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- STEP 2 — Delivery: shipping method + extras --}}
                        <section class="wz-step" data-step="2" hidden>
                            <div class="fset" style="border-bottom:0; padding-bottom:0; margin-bottom:24px;">
                                <h3>{{ __('site.checkout.sec_shipping_method') }}</h3>
                                @include('storefront.partials.shipping-methods')
                            </div>

                            <div class="fset">
                                <h3>{{ __('site.checkout.sec_extras') }}</h3>
                                @include('storefront.partials.checkout-extras')
                            </div>
                        </section>

                        {{-- STEP 3 — Payment --}}
                        <section class="wz-step" data-step="3" hidden>
                            <div class="fset" style="border-bottom:0; padding-bottom:0;">
                                <h3>{{ __('site.checkout.sec_payment') }}</h3>
                                @if ($isStripe)
                                    @include('storefront.partials.stripe-payment')
                                @else
                                    <div class="stub-notice">
                                        <span class="icon">!</span>
                                        <div>
                                            <strong>{{ __('site.checkout.stub_title') }}</strong>
                                            {!! __('site.checkout.stub_body', ['action' => '<em>' . $payLabel . '</em>']) !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        {{-- Single persistent action bar. ONE submit button so the
                             Stripe partial's `form.querySelector('button[type=submit]')`
                             always finds it. Without JS this is just the place-order
                             button under a long form. The wizard JS:
                               - shows the Back button on steps 2+
                               - relabels the primary button "Continue →" on steps 1–2
                               - intercepts submit in the CAPTURE phase: on steps 1–2 it
                                 validates + advances and stops propagation (so the Stripe
                                 bubble-phase submit listener never fires); on step 3 it
                                 lets the event through to Stripe / normal post. --}}
                        <div class="wz-actions">
                            <button type="button" class="wz-back" data-wz-prev hidden>← {{ __('site.checkout.wizard_back') }}</button>
                            <button type="submit" class="pay-btn" data-wz-primary
                                    data-pay-label="{{ $payLabel }}"
                                    data-continue-label="{{ __('site.checkout.wizard_continue') }}">
                                <span data-wz-label>{{ $payLabel }}</span><span data-wz-amount> · <span data-sm-grand>@money($grand)</span></span>
                            </button>
                        </div>
                    </div>

                    {{-- Order summary aside --}}
                    <aside class="osum rv">
                        <h2>{{ __('site.checkout.summary') }}</h2>

                        @foreach ($items as $row)
                            <div class="oitem">
                                <div class="img">
                                    @if ($row['product']->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="">
                                    @else
                                        <span>img</span>
                                    @endif
                                    <span class="qty-pill">{{ $row['quantity'] }}</span>
                                </div>
                                <div>
                                    <div class="nm">{{ $row['product']->name }}</div>
                                    @if (! empty($row['variant']))
                                        <div class="m">— {{ $row['variant']->label }}</div>
                                    @endif
                                </div>
                                <div class="pr">@money($row['subtotal_cents'])</div>
                            </div>
                        @endforeach

                        <hr class="divider">

                        <div class="r">
                            <span>{{ __('site.cart.subtotal') }}</span>
                            <span>@money($subtotal)</span>
                        </div>
                        <div class="r" data-sm-shipping-row>
                            <span>{{ __('site.cart.shipping') }}</span>
                            <span data-sm-shipping>@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                        </div>
                        @if (! empty($discount) && $discountCents > 0)
                            <div class="r discount">
                                <span>{{ $discount->name }}</span>
                                <span>−@money($discountCents)</span>
                            </div>
                        @endif
                        <div class="tot">
                            <span>{{ __('site.cart.total') }}</span>
                            <span data-sm-grand>@money($grand)</span>
                        </div>

                        <div class="secure">{{ __('site.checkout.secure_note') }}</div>
                    </aside>
                </div>
            </form>
        </div>
    </main>

    <script>
        (function () {
            var form = document.querySelector('form[data-wizard]');
            if (! form) return;
            var steps = Array.prototype.slice.call(form.querySelectorAll('.wz-step'));
            if (steps.length < 2) return;

            var stepper = form.querySelector('.wz-steps');
            var stepperItems = stepper ? Array.prototype.slice.call(stepper.querySelectorAll('[data-go-step]')) : [];
            var backBtn = form.querySelector('[data-wz-prev]');
            var primaryBtn = form.querySelector('[data-wz-primary]');
            var primaryLabel = form.querySelector('[data-wz-label]');
            var payLabel = primaryBtn.getAttribute('data-pay-label');
            var continueLabel = primaryBtn.getAttribute('data-continue-label');

            var current = 1;
            var furthest = 1;            // highest step reached (for stepper jump-back)
            var last = steps.length;     // 3

            form.classList.add('wz-on');

            function fieldsIn(step) {
                return Array.prototype.slice.call(step.querySelectorAll('input, select, textarea'))
                    .filter(function (el) { return el.type !== 'hidden' && ! el.disabled; });
            }

            // Native validity check for the current step; reports the first
            // invalid field (shows the browser bubble) and returns false.
            function stepValid(step) {
                var fields = fieldsIn(step);
                for (var i = 0; i < fields.length; i++) {
                    if (! fields[i].checkValidity()) { fields[i].reportValidity(); return false; }
                }
                return true;
            }

            function render() {
                steps.forEach(function (s) {
                    var n = parseInt(s.getAttribute('data-step'), 10);
                    var on = (n === current);
                    s.classList.toggle('is-current', on);
                    s.hidden = ! on;
                });
                stepperItems.forEach(function (li) {
                    var n = parseInt(li.getAttribute('data-go-step'), 10);
                    li.classList.toggle('is-current', n === current);
                    li.classList.toggle('is-done', n < current || (n <= furthest && n !== current));
                });
                if (backBtn) backBtn.hidden = (current === 1);
                // Primary button morphs between "Continue" and the pay label.
                if (primaryLabel) primaryLabel.textContent = (current < last) ? continueLabel : payLabel;
                // The price tag (with its "·" separator) only shows on the
                // final pay action.
                var amount = primaryBtn.querySelector('[data-wz-amount]');
                if (amount) amount.style.display = (current < last) ? 'none' : '';
            }

            function goto(n) {
                current = Math.max(1, Math.min(last, n));
                if (current > furthest) furthest = current;
                render();
                // Scroll the form back into view on step change.
                var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }

            function advance() {
                var step = steps[current - 1];
                if (! stepValid(step)) return;
                goto(current + 1);
            }

            // Intercept submit in the CAPTURE phase so we run before the
            // Stripe partial's bubble-phase submit listener. On non-final
            // steps: validate + advance, and stop the event so Stripe never
            // sees it. On the final step: do nothing → let it proceed.
            form.addEventListener('submit', function (e) {
                if (current < last) {
                    e.preventDefault();
                    e.stopPropagation();
                    advance();
                }
            }, true);

            // Enter key inside a field on a non-final step advances rather
            // than submitting (textareas keep their newline behavior).
            form.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && current < last && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    advance();
                }
            });

            if (backBtn) backBtn.addEventListener('click', function () { goto(current - 1); });

            // Stepper: jump back to any already-reached step.
            stepperItems.forEach(function (li) {
                li.addEventListener('click', function () {
                    var n = parseInt(li.getAttribute('data-go-step'), 10);
                    if (n < current) goto(n);
                });
            });

            render();
        })();
    </script>
@endsection
