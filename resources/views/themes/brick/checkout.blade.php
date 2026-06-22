@php
    $title = __('site.checkout.title');
@endphp
@extends('themes.brick.layout')

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
        .co-wrap { padding: 0 0 60px; }
        .checkout { display: grid; grid-template-columns: 1fr 380px; gap: 28px; align-items: start; }

        .fset { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); padding: 22px 24px; margin-bottom: 22px; }
        .fset h3 { font-family: var(--display); font-size: 13px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
        .fset h3 .num { background: var(--accent); border: 2.5px solid var(--ink); width: 28px; height: 28px; display: grid; place-items: center; font-family: var(--display); font-weight: 800; font-size: 14px; flex-shrink: 0; }
        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-family: var(--display); font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); margin-bottom: 7px; }
        .field label small { text-transform: none; letter-spacing: 0; font-weight: 400; }
        .field input, .field select, .field textarea { border: 2.5px solid var(--ink); background: #fff; padding: 12px 13px; font-family: var(--body); font-size: 14px; color: var(--ink); }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; box-shadow: var(--pop-sm); }
        .field textarea { min-height: 90px; resize: vertical; }

        .co-signed-in { background: var(--accent); border: 2.5px solid var(--ink); padding: 11px 14px; margin-bottom: 16px; font-size: 13px; font-weight: 600; }
        .co-signin-banner { border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); background: var(--paper); padding: 14px 18px; margin-bottom: 26px; font-size: 13px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .co-signin-banner a { font-family: var(--display); font-weight: 700; text-transform: uppercase; font-size: 12px; background: var(--accent); border: 2.5px solid var(--ink); padding: 3px 8px; }

        .errors { border: 2.5px solid #b91c1c; background: #fff; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: var(--ink); font-weight: 600; }
        .errors ul { padding-left: 18px; }

        .stub-notice { background: var(--accent); border: 2.5px solid var(--ink); padding: 14px 18px; font-size: 13px; display: flex; gap: 12px; align-items: flex-start; }
        .stub-notice .icon { display: inline-grid; place-items: center; width: 24px; height: 24px; background: var(--ink); color: var(--accent); font-family: var(--display); font-weight: 800; flex-shrink: 0; }
        .stub-notice strong { display: block; margin-bottom: 4px; font-family: var(--display); text-transform: uppercase; }

        /* order summary */
        .osum { border: 2.5px solid var(--ink); box-shadow: var(--pop); background: var(--paper); padding: 24px; position: sticky; top: 90px; }
        .osum h2 { font-family: var(--display); font-weight: 900; text-transform: uppercase; font-size: 20px; margin-bottom: 20px; }
        .osum .oitem { display: grid; grid-template-columns: 56px 1fr auto; gap: 14px; align-items: center; margin-bottom: 16px; }
        .osum .oitem .img { height: 64px; border: 2.5px solid var(--ink); background: var(--soft); overflow: hidden; position: relative; }
        .osum .oitem .img img { width: 100%; height: 100%; object-fit: cover; }
        .osum .oitem .qty-pill { position: absolute; top: -9px; right: -9px; background: var(--accent); border: 2.5px solid var(--ink); width: 22px; height: 22px; display: grid; place-items: center; font-family: var(--display); font-weight: 800; font-size: 11px; }
        .osum .oitem .nm { font-family: var(--display); font-weight: 700; font-size: 13px; line-height: 1.2; }
        .osum .oitem .m { font-size: 12px; color: var(--muted); }
        .osum .oitem .pr { font-family: var(--display); font-weight: 800; font-size: 14px; }
        .osum .divider { border: 0; border-top: 2.5px solid var(--ink); margin: 16px 0; }
        .osum .r { display: flex; justify-content: space-between; font-size: 14px; margin: 9px 0; font-weight: 600; }
        .osum .tot { display: flex; justify-content: space-between; font-family: var(--display); font-size: 18px; font-weight: 900; text-transform: uppercase; border-top: 2.5px solid var(--ink); padding-top: 16px; margin: 14px 0 16px; }
        .osum .secure { font-family: var(--display); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); text-align: center; margin-top: 10px; }

        /* wizard */
        .wz-steps { display: none; }
        .wz-on .wz-steps { display: flex; align-items: center; gap: 0; list-style: none; margin: 0 0 32px; padding: 0; }
        .wz-steps li { display: flex; align-items: center; gap: 10px; flex: 1; font-family: var(--display); font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--muted); }
        .wz-steps li:not(:last-child)::after { content: ""; flex: 1; height: 2.5px; background: var(--ink); margin: 0 12px; }
        .wz-steps .dot { width: 34px; height: 34px; border: 2.5px solid var(--ink); display: grid; place-items: center; font-family: var(--display); font-weight: 800; font-size: 14px; color: var(--ink); background: var(--paper); flex-shrink: 0; }
        .wz-steps li.is-current .dot { background: var(--accent); }
        .wz-steps li.is-current { color: var(--ink); }
        .wz-steps li.is-done { color: var(--ink); cursor: pointer; }
        .wz-steps li.is-done .dot { background: var(--ink); color: var(--accent); }
        .wz-steps li.is-done .dot::after { content: "✓"; }
        .wz-steps li.is-done .dot .n { display: none; }
        .wz-steps .label { white-space: nowrap; }

        .wz-on .wz-step { display: none; }
        .wz-on .wz-step.is-current { display: block; animation: wzIn .3s ease; }
        @keyframes wzIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

        .wz-actions { display: flex; align-items: center; gap: 12px; margin-top: 26px; }
        .wz-back { background: var(--paper); border: 2.5px solid var(--ink); box-shadow: var(--pop-sm); font-family: var(--display); font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 12px 18px; cursor: pointer; }
        .wz-back:hover { background: var(--accent); }
        .pay-btn { margin-left: auto; display: inline-flex; align-items: baseline; gap: 8px; background: var(--accent); color: var(--ink); border: 2.5px solid var(--ink); box-shadow: var(--pop); padding: 15px 32px; font-family: var(--display); font-size: 13px; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
        .pay-btn:hover { transform: translate(-1px,-1px); box-shadow: var(--pop-lg); }
        .pay-btn:active { transform: translate(5px,5px); box-shadow: 0 0 0 var(--shadow); }
        .pay-btn:focus-visible { outline: none; box-shadow: var(--pop-sm); }
        .pay-btn:disabled { opacity: .5; cursor: not-allowed; }

        @media (max-width: 980px) {
            .checkout { grid-template-columns: 1fr; }
            .osum { position: static; order: -1; }
            .wz-steps .label { display: none; }
        }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .pay-btn, .pay-btn:hover, .pay-btn:active { transform: none; box-shadow: var(--pop); } }
    </style>

    <noscript>
        <style>
            .wz-step[hidden] { display: block !important; }
            .wz-steps { display: none !important; }
            .wz-back { display: none !important; }
        </style>
    </noscript>

    @include('storefront.partials.number-anim')

    <main>
        <div class="wrap co-wrap">
            <div class="ed-head rv">
                <div>
                    <div class="crumb">{{ __('site.checkout.secure_note') }}</div>
                    <h1>{{ __('site.checkout.title') }}</h1>
                </div>
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
                        <section class="wz-step is-current" data-step="1">
                            <div class="fset">
                                <h3><span class="num">1</span>{{ __('site.checkout.sec_contact') }}</h3>
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
                                <h3><span class="num">⌂</span>{{ __('site.checkout.sec_shipping') }}</h3>
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

                        <section class="wz-step" data-step="2" hidden>
                            <div class="fset">
                                <h3><span class="num">2</span>{{ __('site.checkout.sec_shipping_method') }}</h3>
                                @include('storefront.partials.shipping-methods')
                            </div>
                            <div class="fset">
                                <h3><span class="num">+</span>{{ __('site.checkout.sec_extras') }}</h3>
                                @include('storefront.partials.checkout-extras')
                            </div>
                        </section>

                        <section class="wz-step" data-step="3" hidden>
                            <div class="fset">
                                <h3><span class="num">3</span>{{ __('site.checkout.sec_payment') }}</h3>
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

                        <div class="wz-actions">
                            <button type="button" class="wz-back" data-wz-prev hidden>← {{ __('site.checkout.wizard_back') }}</button>
                            <button type="submit" class="pay-btn" data-wz-primary
                                    data-pay-label="{{ $payLabel }}"
                                    data-continue-label="{{ __('site.checkout.wizard_continue') }}">
                                <span data-wz-label>{{ $payLabel }}</span><span data-wz-amount> · <span data-sm-grand>@money($grand)</span></span>
                            </button>
                        </div>
                    </div>

                    <aside class="osum rv">
                        <h2>{{ __('site.checkout.summary') }}</h2>

                        @foreach ($items as $row)
                            <div class="oitem">
                                <div class="img">
                                    @if ($row['product']->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="{{ $row['product']->name }}@if (! empty($row['variant'])) — {{ $row['variant']->label }}@endif">
                                    @else
                                        <div class="ph" style="width:100%;height:100%"><span>img</span></div>
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
            var furthest = 1;
            var last = steps.length;

            form.classList.add('wz-on');

            function fieldsIn(step) {
                return Array.prototype.slice.call(step.querySelectorAll('input, select, textarea'))
                    .filter(function (el) { return el.type !== 'hidden' && ! el.disabled; });
            }

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
                if (primaryLabel) primaryLabel.textContent = (current < last) ? continueLabel : payLabel;
                var amount = primaryBtn.querySelector('[data-wz-amount]');
                if (amount) amount.style.display = (current < last) ? 'none' : '';
            }

            function goto(n) {
                current = Math.max(1, Math.min(last, n));
                if (current > furthest) furthest = current;
                render();
                var top = form.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }

            function advance() {
                var step = steps[current - 1];
                if (! stepValid(step)) return;
                goto(current + 1);
            }

            form.addEventListener('submit', function (e) {
                if (current < last) {
                    e.preventDefault();
                    e.stopPropagation();
                    advance();
                }
            }, true);

            form.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && current < last && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    advance();
                }
            });

            if (backBtn) backBtn.addEventListener('click', function () { goto(current - 1); });

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
