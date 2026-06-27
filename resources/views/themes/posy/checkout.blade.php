@php
    $title = __('site.checkout.title');
@endphp
@extends('themes.posy.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $shipping = $shipping_cents ?? 0;
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal + $shipping - $discountCents);
        $defaultAddress = $defaultAddress ?? [];
        $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG');
        $isStripe = ($payment_mode ?? 'stub') === 'stripe';
        $payLabel = $isStripe ? __('site.checkout.pay_now') : __('site.checkout.action_place_order');
        $wizardSteps = [
            1 => __('site.checkout.step_details'),
            2 => __('site.checkout.step_delivery'),
            3 => __('site.checkout.step_payment'),
        ];
    @endphp

    <style>
        .co-wrap { padding: 0 0 80px; }
        .checkout { display: grid; grid-template-columns: 1fr 380px; gap: 32px; align-items: start; }

        /* numbered field sets */
        .fset { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 26px 28px; margin-bottom: 22px; box-shadow: 0 16px 38px -26px rgba(40, 50, 31, .4); }
        .fset h3 { font-family: var(--display); font-size: 19px; font-weight: 400; margin-bottom: 20px; display: flex; align-items: center; gap: 14px; }
        .fset h3 .num { background: var(--accent); color: #fbfcf5; width: 30px; height: 30px; border-radius: 99px; display: grid; place-items: center; font-family: var(--display); font-size: 15px; flex-shrink: 0; }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .frow + .frow { margin-top: 16px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .field label small { text-transform: none; letter-spacing: 0; font-weight: 400; }
        .field input, .field select, .field textarea { border: 1px solid var(--line); background: var(--card); border-radius: 10px; padding: 12px 14px; font-family: var(--body); font-size: 14px; color: var(--ink); transition: border-color .2s ease; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); }
        .field textarea { min-height: 90px; resize: vertical; }

        /* signed-in / sign-in banners */
        .co-signed-in { background: color-mix(in srgb, var(--accent) 12%, var(--card)); border: 1px solid var(--line); border-radius: 10px; padding: 12px 15px; margin-bottom: 18px; font-size: 13px; }
        .co-signin-banner { background: var(--soft); border: 1px solid var(--line); border-radius: 12px; padding: 14px 20px; margin-bottom: 28px; font-size: 13.5px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .co-signin-banner a { color: var(--accent); font-weight: 600; border-bottom: 1px solid currentColor; padding-bottom: 1px; }

        .errors { border: 1px solid #c0573e; background: color-mix(in srgb, #cf8f6e 12%, var(--card)); border-radius: 12px; padding: 14px 20px; margin-bottom: 26px; font-size: 13.5px; color: var(--ink); }
        .errors ul { padding-left: 20px; }
        .errors li::marker { color: #c0573e; }

        /* stub payment notice */
        .stub-notice { background: color-mix(in srgb, var(--accent) 10%, var(--card)); border: 1px solid var(--line); border-radius: 12px; padding: 16px 20px; font-size: 13.5px; display: flex; gap: 14px; align-items: flex-start; }
        .stub-notice .icon { display: inline-grid; place-items: center; width: 26px; height: 26px; border-radius: 99px; background: var(--accent); color: #fbfcf5; font-family: var(--display); flex-shrink: 0; }
        .stub-notice strong { display: block; margin-bottom: 4px; font-family: var(--display); font-weight: 400; }
        .stub-notice em { font-family: var(--serif); font-style: italic; color: var(--accent); }

        /* order summary */
        .osum { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 28px; position: sticky; top: 96px; box-shadow: 0 16px 38px -22px rgba(40, 50, 31, .4); }
        .osum h2 { font-family: var(--display); font-weight: 400; font-size: 26px; margin-bottom: 22px; }
        .osum h2 em { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .osum .oitem { display: grid; grid-template-columns: 56px 1fr auto; gap: 14px; align-items: center; margin-bottom: 18px; }
        .osum .oitem .img { height: 64px; border-radius: 8px; overflow: hidden; position: relative; }
        .osum .oitem .img img { width: 100%; height: 100%; object-fit: cover; }
        .osum .oitem .qty-pill { position: absolute; top: -8px; right: -8px; background: var(--accent); color: #fbfcf5; width: 22px; height: 22px; border-radius: 99px; display: grid; place-items: center; font-family: var(--display); font-size: 11px; box-shadow: 0 2px 6px rgba(40, 50, 31, .25); }
        .osum .oitem .nm { font-family: var(--display); font-size: 15px; line-height: 1.25; }
        .osum .oitem .m { font-size: 12px; color: var(--muted); }
        .osum .oitem .pr { font-family: var(--serif); font-size: 17px; color: var(--accent); }
        .osum .divider { border: 0; border-top: 1px solid var(--line); margin: 18px 0; }
        .osum .r { display: flex; justify-content: space-between; font-size: 14px; margin: 10px 0; color: var(--ink); }
        .osum .r.discount { color: var(--accent); }
        .osum .tot { display: flex; justify-content: space-between; align-items: baseline; font-family: var(--display); font-size: 21px; border-top: 1px solid var(--line); padding-top: 18px; margin: 16px 0 18px; }
        .osum .tot span:last-child { font-family: var(--serif); font-style: italic; color: var(--accent); }
        .osum .secure { font-size: 12px; letter-spacing: .04em; color: var(--muted); text-align: center; margin-top: 12px; }

        /* wizard stepper */
        .wz-steps { display: none; }
        .wz-on .wz-steps { display: flex; align-items: center; gap: 0; list-style: none; margin: 0 0 34px; padding: 0; }
        .wz-steps li { display: flex; align-items: center; gap: 11px; flex: 1; font-size: 12px; letter-spacing: .04em; text-transform: uppercase; font-weight: 600; color: var(--muted); }
        .wz-steps li:not(:last-child)::after { content: ""; flex: 1; height: 1px; background: var(--line); margin: 0 14px; }
        .wz-steps .dot { width: 34px; height: 34px; border: 1px solid var(--line); border-radius: 99px; display: grid; place-items: center; font-family: var(--display); font-size: 14px; color: var(--ink); background: var(--card); flex-shrink: 0; transition: background-color .2s ease, color .2s ease, border-color .2s ease; }
        .wz-steps li.is-current .dot { background: var(--accent); color: #fbfcf5; border-color: var(--accent); }
        .wz-steps li.is-current { color: var(--ink); }
        .wz-steps li.is-done { color: var(--ink); cursor: pointer; }
        .wz-steps li.is-done .dot { background: var(--ink); color: var(--bg); border-color: var(--ink); }
        .wz-steps li.is-done .dot::after { content: "✓"; }
        .wz-steps li.is-done .dot .n { display: none; }
        .wz-steps .label { white-space: nowrap; }

        .wz-on .wz-step { display: none; }
        .wz-on .wz-step.is-current { display: block; animation: wzIn .35s ease; }
        @keyframes wzIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        .wz-actions { display: flex; align-items: center; gap: 14px; margin-top: 28px; }
        .wz-back { background: transparent; border: 1px solid var(--ink); border-radius: 99px; color: var(--ink); font-size: 13px; font-weight: 600; padding: 13px 24px; transition: background-color .25s ease, color .25s ease; }
        .wz-back:hover { background: var(--ink); color: var(--bg); }
        .pay-btn { margin-left: auto; display: inline-flex; align-items: baseline; gap: 8px; background: var(--accent); color: #fbfcf5; border: 1px solid var(--accent); border-radius: 99px; padding: 15px 34px; font-size: 14px; font-weight: 600; transition: filter .25s ease, transform .25s ease; }
        .pay-btn:hover { filter: brightness(1.08); transform: translateY(-2px); }
        .pay-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; filter: none; }
        .pay-btn [data-wz-amount] { font-family: var(--serif); font-style: italic; }

        @media (max-width: 980px) {
            .checkout { grid-template-columns: 1fr; }
            .osum { position: static; order: -1; }
            .wz-steps .label { display: none; }
        }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .pay-btn:hover { transform: none; } }
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
            <div class="page-head reveal" style="padding-bottom: 18px;">
                <div class="crumb">{{ __('site.checkout.secure_note') }}</div>
                <h1>{{ __('site.checkout.title') }}</h1>
            </div>

            @guest('customer')
                @if ($store->showsAccountUi())
                    <div class="co-signin-banner reveal">
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
                    <div class="co-main reveal">
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
                                <h3><span class="num">❧</span>{{ __('site.checkout.sec_shipping') }}</h3>
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

                    <aside class="osum reveal">
                        <h2>{{ __('site.checkout.summary') }}</h2>

                        @foreach ($items as $row)
                            <div class="oitem">
                                <div class="img {{ $loop->even ? 'bloomph' : 'ph' }}">
                                    @if ($row['product']->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="{{ $row['product']->name }}@if (! empty($row['variant'])) — {{ $row['variant']->label }}@endif">
                                    @endif
                                    <span class="qty-pill">{{ $row['quantity'] }}</span>
                                </div>
                                <div>
                                    <div class="nm">{{ $row['product']->name }}</div>
                                    @if (! empty($row['variant']))
                                        <div class="m">{{ $row['variant']->label }}</div>
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
