{{--
 | Sankevi — checkout. The dispatch desk: three ruled sheets with the load
 | docket standing beside them. Same wizard mechanics as every other theme;
 | only the paper changed.
 |
 | THREE PAYMENT MODES, one file. $payment_mode comes from CheckoutController:
 |
 |   'enquiry'  this yard takes NO money. Sheet 3 is not a till — it is a
 |              review-and-send sheet: what happens next, what was entered,
 |              and a plain statement that nothing is being charged. No
 |              Stripe include, no card fields, no amount on the button.
 |   'stripe'   unchanged — sheet 3 mounts storefront.partials.stripe-payment.
 |   'stub'     unchanged — sheet 3 shows the demo-payment notice.
 |
 | Everything the controller validates keeps its name and everything the
 | wizard/shipping JS binds to keeps its hook. See the contract list on the
 | <form> below.
--}}
@php
    $isEnquiry = ($payment_mode ?? 'stub') === 'enquiry';
    $title = $isEnquiry ? __('site.storefront.sankevi.co_h1') : __('site.checkout.title');
@endphp
@extends('themes.sankevi.layout')

@section('content')
    @php
        $subtotal = $total_cents ?? 0;
        $shipping = $shipping_cents ?? 0;
        $discountCents = $discount_cents ?? 0;
        $grand = max(0, $subtotal + $shipping - $discountCents);
        $defaultAddress = $defaultAddress ?? [];
        $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG');
        $isStripe = ($payment_mode ?? 'stub') === 'stripe';

        $payLabel = $isEnquiry
            ? __('site.storefront.sankevi.send_request')
            : ($isStripe ? __('site.checkout.pay_now') : __('site.checkout.action_place_order'));

        // Third step is a review, not a payment, when nothing is being charged.
        $wizardSteps = [
            1 => __('site.checkout.step_details'),
            2 => __('site.checkout.step_delivery'),
            3 => $isEnquiry ? __('site.storefront.sankevi.step_review') : __('site.checkout.step_payment'),
        ];

        $headCrumb = $isEnquiry ? __('site.storefront.sankevi.co_crumb') : __('site.checkout.secure_note');
        $headTitle = $isEnquiry ? __('site.storefront.sankevi.co_h1') : __('site.checkout.title');
        $sumTitle  = $isEnquiry ? __('site.storefront.sankevi.req_panel_h') : __('site.checkout.summary');
        $totLabel  = $isEnquiry ? __('site.storefront.sankevi.req_est_label') : __('site.cart.total');
        $footNote  = $isEnquiry ? __('site.storefront.sankevi.req_no_payment') : __('site.checkout.secure_note');

        // Same axis lookup as the cart — the docket must show the customer the
        // dimensions they picked, not a squashed variant label. One query.
        $variantIds = $items->map(fn ($r) => $r['variant']?->id ?? null)->filter()->values()->all();
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
        /* padding-bottom, NOT the shorthand: `padding: 0 0 90px` would zero the
           .wrap gutter and let the sheets run to the viewport edge, out of line
           with the header and the footer. */
        .co-wrap { padding-bottom: 90px; }
        .checkout { display: grid; grid-template-columns: 1fr 366px; gap: 46px; align-items: start; }

        /* ===== the sheets ===== */
        .fset { background: var(--surface); border: 1px solid var(--line); padding: 28px 30px 30px; margin-bottom: 22px; }
        .fset h3 { display: flex; align-items: center; gap: 14px; font-family: var(--display); font-weight: 500; font-size: 24px; line-height: 1.1; padding-bottom: 16px; margin-bottom: 22px; border-bottom: 1px solid var(--line); }
        .fset h3 .num { flex-shrink: 0; width: 30px; height: 30px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-family: var(--body); font-size: 12px; font-weight: 600; }

        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .frow + .frow { margin-top: 18px; }
        .field { display: flex; flex-direction: column; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
        .field label small { text-transform: none; letter-spacing: .02em; color: var(--faint); }
        .field input, .field select, .field textarea { background-color: var(--bg); border: 1px solid var(--line2); padding: 13px 15px; font-family: var(--body); font-size: 14.5px; color: var(--txt); transition: border-color .25s ease; }
        /* background-COLOR, not the shorthand: the layout draws the select's
           chevron as a background-image and `background:` resets it. The
           extra right padding is the room that arrow sits in. */
        .field select { padding-right: 38px; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); }
        .field textarea { min-height: 96px; resize: vertical; }

        /* The shared shipping-method + extras partials ship their own light-UI
           styles (white cards, black accents). Out-specify them here rather
           than forking the partials — every theme gets to dress them. */
        .co-main .sm-option { background: var(--surface2); border: 1px solid var(--line2); border-radius: 0; color: var(--txt); transition: border-color .25s ease, background-color .25s ease; }
        .co-main .sm-option:hover { border-color: var(--accent); }
        .co-main .sm-option input { accent-color: var(--accent); }
        .co-main .sm-option:has(input:checked) { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 9%, var(--surface2)); }
        .co-main .sm-label { font-family: var(--body); font-size: 11px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; }
        .co-main .sm-cost { font-family: var(--display); font-size: 17px; font-variant-numeric: tabular-nums; color: var(--accent-ink); }
        .co-main .sm-desc { color: var(--muted); font-size: 12.5px; }
        .co-main .ce-label { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--muted); }
        .co-main .ce-label small { color: var(--faint); text-transform: none; letter-spacing: .02em; }
        .co-main .ce-field textarea { background: var(--bg); border: 1px solid var(--line2); border-radius: 0; color: var(--txt); font-family: var(--body); font-size: 14.5px; padding: 13px 15px; }
        .co-main .ce-field textarea:focus { border-color: var(--accent); }
        .co-main .ce-check { color: var(--muted); font-size: 13px; }
        .co-main .ce-check input { accent-color: var(--accent); }

        .co-signed-in { background: color-mix(in srgb, var(--accent) 10%, var(--surface)); border: 1px solid var(--line2); padding: 12px 15px; margin-bottom: 20px; font-size: 12.5px; letter-spacing: .04em; }
        .co-signin-banner { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; background: var(--surface); border: 1px solid var(--line); padding: 15px 22px; margin-bottom: 30px; font-size: 11px; font-weight: 500; letter-spacing: .16em; text-transform: uppercase; color: var(--muted); }
        .co-signin-banner a { color: var(--accent-ink); border-bottom: 1px solid currentColor; padding-bottom: 1px; }

        .errors { border: 1px solid #b4614a; border-left-width: 3px; background: color-mix(in srgb, #b4614a 12%, var(--surface)); padding: 15px 20px; margin-bottom: 28px; font-size: 13.5px; }
        .errors ul { list-style: none; }
        .errors li { display: flex; gap: 10px; }
        .errors li::before { content: "—"; color: #d08a70; }

        .stub-notice { display: flex; gap: 16px; align-items: flex-start; background: var(--surface2); border: 1px solid var(--line2); padding: 18px 22px; font-size: 13.5px; color: var(--muted); }
        .stub-notice .icon { flex-shrink: 0; width: 28px; height: 28px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-size: 14px; font-weight: 600; }
        .stub-notice strong { display: block; margin-bottom: 5px; font-family: var(--display); font-weight: 500; font-size: 19px; color: var(--txt); }
        .stub-notice em { font-style: normal; font-weight: 600; color: var(--accent-ink); }

        /* ===== ENQUIRY — the send sheet ===== */
        /* what happens next: three ruled steps, numbered in the margin */
        .nx { list-style: none; counter-reset: nx; }
        .nx li { position: relative; counter-increment: nx; display: grid; grid-template-columns: 34px 1fr; gap: 16px; align-items: start; padding: 15px 0; border-bottom: 1px solid var(--line); font-size: 14.5px; color: var(--muted); }
        .nx li:last-child { border-bottom: none; padding-bottom: 0; }
        .nx li::before { content: counter(nx, decimal-leading-zero); font-family: var(--body); font-size: 10.5px; font-weight: 500; letter-spacing: .18em; color: var(--accent-ink); padding-top: 5px; }

        /* the no-payment slab — the one place on the page allowed to be loud */
        .nopay { display: flex; gap: 18px; align-items: flex-start; background: var(--moss); border: 1px solid color-mix(in srgb, var(--accent) 30%, var(--moss)); padding: 22px 26px; margin-bottom: 22px; color: #f1ebdd; }
        .nopay .ic { flex-shrink: 0; width: 34px; height: 34px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-family: var(--display); font-size: 20px; font-weight: 600; line-height: 1; }
        .nopay strong { display: block; margin-bottom: 6px; font-family: var(--display); font-weight: 500; font-size: 21px; line-height: 1.1; }
        .nopay p { font-size: 14px; line-height: 1.6; color: color-mix(in srgb, #f1ebdd 78%, transparent); }

        /* the read-back — everything the customer typed, ruled like a docket */
        .rv + .rv { margin-top: 26px; }
        .rv-h { display: flex; align-items: baseline; justify-content: space-between; gap: 14px; padding-bottom: 10px; margin-bottom: 4px; border-bottom: 1px solid var(--line2); }
        .rv-h span { font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .rv-h button { background: none; border: none; padding: 0; font-family: var(--body); font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--accent-ink); border-bottom: 1px solid color-mix(in srgb, var(--accent) 45%, transparent); transition: border-color .25s ease; }
        .rv-h button:hover { border-color: var(--accent); }
        .rv-row { display: grid; grid-template-columns: 148px 1fr; gap: 18px; padding: 11px 0; border-bottom: 1px solid var(--line); font-size: 14.5px; }
        .rv-row:last-child { border-bottom: none; }
        .rv-row .k { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); padding-top: 3px; }
        .rv-row .v { color: var(--txt); white-space: pre-line; word-break: break-word; }
        .rv-row .v:empty::before { content: "—"; color: var(--faint); }

        /* ===== the docket ===== */
        .osum { position: sticky; top: calc(var(--header-height) + 22px); background: var(--surface); border: 1px solid var(--line); padding: 30px 28px 32px; }
        .osum h2 { font-family: var(--display); font-weight: 500; font-size: 27px; line-height: 1; padding-bottom: 16px; margin-bottom: 22px; border-bottom: 1px solid var(--line2); }
        .osum .oitem { display: grid; grid-template-columns: 56px 1fr auto; gap: 16px; align-items: center; margin-bottom: 18px; }
        .osum .oitem .img { position: relative; aspect-ratio: 4 / 5; overflow: hidden; background: linear-gradient(150deg, var(--surface2), #191510); display: grid; place-items: center; }
        .osum .oitem .img img { width: 100%; height: 100%; object-fit: cover; }
        .osum .oitem .qty-pill { position: absolute; top: 0; right: 0; min-width: 21px; height: 21px; padding: 0 5px; display: grid; place-items: center; background: var(--accent); color: var(--on-accent); font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums; }
        .osum .oitem .nm { font-family: var(--display); font-weight: 500; font-size: 18px; line-height: 1.15; }
        .osum .oitem .m { margin-top: 4px; font-size: 10px; font-weight: 500; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
        .osum .oitem .m b { font-weight: 500; color: var(--faint); }
        .osum .oitem .pr { font-family: var(--display); font-weight: 500; font-size: 17px; font-variant-numeric: tabular-nums; }
        .osum .divider { border: 0; border-top: 1px solid var(--line); margin: 20px 0; }
        .osum .r { display: flex; justify-content: space-between; gap: 12px; margin: 11px 0; font-size: 13.5px; color: var(--muted); }
        .osum .r span:first-child { font-size: 10.5px; font-weight: 500; letter-spacing: .18em; text-transform: uppercase; }
        .osum .r span:last-child { font-variant-numeric: tabular-nums; color: var(--txt); }
        .osum .r.discount span, .osum .r.discount span:last-child { color: var(--accent-ink); }
        .osum .tot { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; padding-top: 18px; margin: 18px 0 16px; border-top: 1px solid var(--line2); font-family: var(--display); font-weight: 500; font-size: 21px; }
        .osum .tot .v { font-size: 25px; font-variant-numeric: tabular-nums; color: var(--accent-ink); }
        /* outside [data-sm-grand]: the shipping-method JS rewrites that node */
        .osum .tot .approx { margin-right: 4px; font-size: 19px; color: var(--faint); }
        .osum .est-note { margin: -4px 0 16px; padding-left: 13px; border-left: 1px solid var(--line2); font-size: 12.5px; line-height: 1.6; color: var(--muted); }
        .osum .secure { text-align: center; font-size: 10px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }

        /* ===== stepper — three cuts ticked off along a rule ===== */
        .wz-steps { display: none; }
        .wz-on .wz-steps { display: flex; align-items: center; list-style: none; margin: 0 0 38px; padding: 0; }
        .wz-steps li { display: flex; align-items: center; gap: 12px; flex: 1; font-size: 10.5px; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--faint); }
        .wz-steps li:not(:last-child)::after { content: ""; flex: 1; height: 1px; margin: 0 16px; background: var(--line); }
        .wz-steps .dot { flex-shrink: 0; width: 32px; height: 32px; display: grid; place-items: center; border: 1px solid var(--line2); font-size: 12px; font-weight: 500; color: var(--muted); transition: background-color .25s ease, color .25s ease, border-color .25s ease; }
        .wz-steps li.is-current { color: var(--txt); }
        .wz-steps li.is-current .dot { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }
        .wz-steps li.is-done { color: var(--muted); cursor: pointer; }
        .wz-steps li.is-done .dot { border-color: var(--accent); color: var(--accent-ink); }
        .wz-steps li.is-done .dot::after { content: "✓"; }
        .wz-steps li.is-done .dot .n { display: none; }
        .wz-steps .label { white-space: nowrap; }

        .wz-on .wz-step { display: none; }
        .wz-on .wz-step.is-current { display: block; animation: wzIn .4s cubic-bezier(.19, .74, .16, 1); }
        @keyframes wzIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

        .wz-actions { display: flex; align-items: center; gap: 16px; margin-top: 30px; flex-wrap: wrap; }
        /* .btn sets display:inline-flex — an author declaration that outranks
           the UA [hidden] rule the wizard uses to hide Back on step 1. */
        .wz-actions [hidden] { display: none !important; }
        .pay-btn { margin-left: auto; align-items: baseline; }
        .pay-btn [data-wz-amount] { font-variant-numeric: tabular-nums; }

        @media (max-width: 980px) {
            .checkout { grid-template-columns: 1fr; gap: 30px; }
            .osum { position: relative; top: auto; order: -1; }
            .wz-steps .label { display: none; }
        }
        @media (max-width: 560px) {
            .frow { grid-template-columns: 1fr; }
            .fset { padding: 22px 20px 24px; }
            .osum { padding: 24px 20px 26px; }
            .pay-btn { margin-left: 0; width: 100%; }
            .rv-row { grid-template-columns: 1fr; gap: 4px; }
            .nopay { padding: 20px; gap: 14px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .wz-on .wz-step.is-current { animation: none; }
            .wz-steps .dot { transition: none; }
        }
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
            <div class="page-head reveal" style="padding-bottom: 22px;">
                <div class="crumb">{{ $headCrumb }}</div>
                <h1>{{ $headTitle }}</h1>
            </div>

            @guest('customer')
                @if ($store->showsAccountUi())
                    <div class="co-signin-banner reveal" style="margin-top: 30px;">
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

            {{-- CONTRACT — do not rename any of these:
                 field names (customer_email, customer_name, customer_phone,
                 address_line, address_region, city, postal_code, country,
                 shipping_method, notes, marketing_opt_in) are what
                 CheckoutController::validatePayload() expects; [data-wizard],
                 .wz-step[data-step], [data-go-step], [data-wz-prev],
                 [data-wz-primary]/[data-wz-label]/[data-wz-amount] drive the
                 stepper; [data-sm-shipping], [data-sm-shipping-row] and
                 [data-sm-grand] are written by the shipping-methods partial. --}}
            <form method="post" action="/checkout" data-wizard style="margin-top: 34px;">
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
                            <div class="fset cut">
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

                            <div class="fset cut">
                                {{-- continuation of step 1, not its own step --}}
                                <h3><span class="num">↳</span>{{ __('site.checkout.sec_shipping') }}</h3>
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
                            <div class="fset cut">
                                <h3><span class="num">2</span>{{ __('site.checkout.sec_shipping_method') }}</h3>
                                @include('storefront.partials.shipping-methods')
                            </div>
                            <div class="fset cut">
                                <h3><span class="num">+</span>{{ __('site.checkout.sec_extras') }}</h3>
                                @include('storefront.partials.checkout-extras')
                            </div>
                        </section>

                        <section class="wz-step" data-step="3" hidden>
                            @if ($isEnquiry)
                                {{-- No till on this sheet: the customer is sending a
                                     list, so they get told what happens to it, then
                                     shown back exactly what they wrote. --}}
                                <div class="nopay cut">
                                    <span class="ic" aria-hidden="true">i</span>
                                    <div>
                                        <strong>{{ __('site.storefront.sankevi.no_pay_h') }}</strong>
                                        <p>{{ __('site.storefront.sankevi.no_pay_p') }}</p>
                                    </div>
                                </div>

                                <div class="fset cut">
                                    <h3><span class="num">3</span>{{ __('site.storefront.sankevi.next_h') }}</h3>
                                    <ol class="nx">
                                        <li>{{ __('site.storefront.sankevi.next_1') }}</li>
                                        <li>{{ __('site.storefront.sankevi.next_2') }}</li>
                                        <li>{{ __('site.storefront.sankevi.next_3') }}</li>
                                    </ol>
                                </div>

                                <div class="fset cut">
                                    <h3><span class="num">✓</span>{{ __('site.storefront.sankevi.sec_review') }}</h3>

                                    <div class="rv">
                                        <div class="rv-h">
                                            <span>{{ __('site.storefront.sankevi.rv_contact') }}</span>
                                            <button type="button" data-rv-edit="1">{{ __('site.storefront.sankevi.rv_edit') }}</button>
                                        </div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.full_name') }}</span><span class="v" data-rv="customer_name"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.email') }}</span><span class="v" data-rv="customer_email"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.phone') }}</span><span class="v" data-rv="customer_phone"></span></div>
                                    </div>

                                    <div class="rv">
                                        <div class="rv-h">
                                            <span>{{ __('site.storefront.sankevi.rv_delivery') }}</span>
                                            <button type="button" data-rv-edit="2">{{ __('site.storefront.sankevi.rv_edit') }}</button>
                                        </div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.street') }}</span><span class="v" data-rv="address_line"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.city') }}</span><span class="v" data-rv="city_line"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.country') }}</span><span class="v" data-rv="country_label"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.checkout.sec_shipping_method') }}</span><span class="v" data-rv="shipping_label"></span></div>
                                        <div class="rv-row"><span class="k">{{ __('site.storefront.sankevi.rv_notes') }}</span><span class="v" data-rv="notes"></span></div>
                                    </div>
                                </div>
                            @elseif ($isStripe)
                                <div class="fset cut">
                                    <h3><span class="num">3</span>{{ __('site.checkout.sec_payment') }}</h3>
                                    @include('storefront.partials.stripe-payment')
                                </div>
                            @else
                                <div class="fset cut">
                                    <h3><span class="num">3</span>{{ __('site.checkout.sec_payment') }}</h3>
                                    <div class="stub-notice">
                                        <span class="icon">!</span>
                                        <div>
                                            <strong>{{ __('site.checkout.stub_title') }}</strong>
                                            {!! __('site.checkout.stub_body', ['action' => '<em>' . $payLabel . '</em>']) !!}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <div class="wz-actions">
                            <button type="button" class="btn outline wz-back" data-wz-prev hidden>← {{ __('site.checkout.wizard_back') }}</button>
                            <button type="submit" class="btn pay-btn" data-wz-primary
                                    data-pay-label="{{ $payLabel }}"
                                    data-continue-label="{{ __('site.checkout.wizard_continue') }}">
                                {{-- The amount rides the button only when money is
                                     actually changing hands. In enquiry mode the
                                     span is omitted entirely; the wizard's render()
                                     null-checks it. --}}
                                <span data-wz-label>{{ $payLabel }}</span>@unless ($isEnquiry)<span data-wz-amount> · <span data-sm-grand>@money($grand)</span></span>@endunless
                            </button>
                        </div>
                    </div>

                    <aside class="osum cut reveal">
                        <h2>{{ $sumTitle }}</h2>

                        @foreach ($items as $row)
                            @php $rowDims = ! empty($row['variant']) ? ($dimsByVariant[$row['variant']->id] ?? []) : []; @endphp
                            <div class="oitem">
                                <div class="img cut cut-sm">
                                    @if ($row['product']->image_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="{{ $row['product']->name }}@if (! empty($row['variant'])) — {{ $row['variant']->label }}@endif">
                                    @else
                                        <span class="board-glyph xs" aria-hidden="true"><i></i></span>
                                    @endif
                                    <span class="qty-pill">{{ $row['quantity'] }}</span>
                                </div>
                                <div>
                                    <div class="nm">{{ $row['product']->name }}</div>
                                    @if ($rowDims !== [])
                                        {{-- the cut, spelled out — same reading as the cart --}}
                                        <div class="m">@foreach ($rowDims as $d)<b>{{ $d['k'] }}</b> {{ $d['v'] }}@if (! $loop->last) · @endif @endforeach</div>
                                    @elseif (! empty($row['variant']))
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
                            <span>{{ $totLabel }}</span>
                            {{-- In enquiry mode this stays a live figure (the delivery
                                 picker rewrites it) but is framed as an estimate — the
                                 same framing the cart used, so nothing changes meaning
                                 between the two pages. --}}
                            <span class="v">@if ($isEnquiry)<span class="approx" aria-hidden="true">≈</span>@endif<span data-sm-grand>@money($grand)</span></span>
                        </div>
                        @if ($isEnquiry)
                            <p class="est-note">{{ __('site.storefront.sankevi.req_est_note') }}</p>
                        @endif

                        <div class="secure">{{ $footNote }}</div>
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

            // Enquiry mode only: read the form back to the customer on the final
            // sheet. No-ops on stripe/stub, where [data-rv] doesn't exist.
            var reviewFields = Array.prototype.slice.call(form.querySelectorAll('[data-rv]'));
            function val(name) {
                var el = form.elements[name];
                return el && typeof el.value === 'string' ? el.value.trim() : '';
            }
            function syncReview() {
                if (! reviewFields.length) return;
                var country = form.elements['country'];
                var countryLabel = (country && country.selectedIndex >= 0)
                    ? country.options[country.selectedIndex].text
                    : '';
                var shipping = form.querySelector('input[name="shipping_method"]:checked');
                var shippingLabel = '';
                if (shipping) {
                    var lbl = shipping.closest('label');
                    var nameEl = lbl ? lbl.querySelector('.sm-label') : null;
                    shippingLabel = nameEl ? nameEl.textContent.trim() : shipping.value;
                }
                var cityLine = [val('postal_code'), val('city'), val('address_region')]
                    .filter(Boolean).join(', ');

                var map = {
                    customer_name: val('customer_name'),
                    customer_email: val('customer_email'),
                    customer_phone: val('customer_phone'),
                    address_line: val('address_line'),
                    city_line: cityLine,
                    country_label: countryLabel,
                    shipping_label: shippingLabel,
                    notes: val('notes'),
                };
                reviewFields.forEach(function (el) {
                    var key = el.getAttribute('data-rv');
                    // Left empty on purpose when there's no value — CSS prints
                    // the em dash, so an absent phone doesn't read as a blank bug.
                    el.textContent = map[key] || '';
                });
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
                if (current === last) syncReview();
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

            form.querySelectorAll('[data-rv-edit]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    goto(parseInt(btn.getAttribute('data-rv-edit'), 10));
                });
            });

            render();
        })();
    </script>
@endsection
