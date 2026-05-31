@php
    $title = __('site.checkout.title');
    $subtotal = $total_cents ?? 0;
    $shipping = $shipping_cents ?? 0;
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
    $defaultAddress = $defaultAddress ?? [];
    $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG');
    $isStripe = ($payment_mode ?? 'stub') === 'stripe';
    $payLabel = $isStripe ? __('site.checkout.pay_now') : __('site.checkout.action_place_order');
    $wizardSteps = [1 => __('site.checkout.step_details'), 2 => __('site.checkout.step_delivery'), 3 => __('site.checkout.step_payment')];
@endphp
@extends('themes.tech.layout')

@section('content')
    <style>
        .co-wrap { padding: 34px 0 60px; }
        .co-wrap > h1 { font-family: var(--archivo); font-weight: 800; font-size: 40px; letter-spacing: -.02em; margin-bottom: 24px; }
        .wz-steps { display: none; }
        .wz-on .wz-steps { display: flex; gap: 0; list-style: none; margin: 0 0 28px; padding: 0; }
        .wz-steps li { display: flex; align-items: center; gap: 10px; flex: 1; font-family: var(--mono); font-size: 11px; color: var(--faint); }
        .wz-steps li:not(:last-child)::after { content: ""; flex: 1; height: 1px; background: var(--line); margin: 0 12px; }
        .wz-steps .dot { width: 26px; height: 26px; border: 1px solid var(--line); border-radius: 6px; display: grid; place-items: center; font-weight: 700; flex-shrink: 0; }
        .wz-steps li.is-current { color: var(--txt); } .wz-steps li.is-current .dot { border-color: var(--accent); color: var(--accent); }
        .wz-steps li.is-done { color: var(--txt); cursor: pointer; } .wz-steps li.is-done .dot { background: var(--accent); color: #0a0b0e; border-color: var(--accent); }

        .checkout { display: grid; grid-template-columns: 1fr 400px; gap: 50px; align-items: start; }
        .wz-on .wz-step { display: none; } .wz-on .wz-step.is-current { display: block; animation: fade .3s ease; }
        @keyframes fade { from { opacity: 0; transform: translateY(8px); } }

        .fset { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 26px; margin-bottom: 16px; }
        .fset h3 { font-size: 14px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
        .fset h3 .num { width: 24px; height: 24px; background: var(--accent); color: #0a0b0e; border-radius: 6px; display: grid; place-items: center; font-family: var(--mono); font-size: 12px; font-weight: 700; }
        .frow { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .field { display: flex; flex-direction: column; } .field.full { grid-column: 1/-1; }
        .field label { font-family: var(--mono); font-size: 11px; color: var(--muted); margin-bottom: 7px; }
        .field input, .field select, .field textarea { background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 13px 14px; color: var(--txt); font-family: inherit; font-size: 14px; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: var(--accent); }
        .co-signed-in { background: var(--surface2); border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px; font-family: var(--mono); font-size: 12px; }
        .co-signin-banner { background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .co-signin-banner a { color: var(--accent); }
        .errors { border: 1px solid #ff5c5c; background: rgba(255,92,92,.06); color: #ff8a8a; padding: 14px 18px; border-radius: 10px; margin-bottom: 22px; font-size: 13px; } .errors ul { padding-left: 18px; }
        .stub-notice { background: var(--surface2); border: 1px solid var(--line); border-radius: 8px; padding: 14px 18px; font-size: 13px; display: flex; gap: 12px; }
        .stub-notice .icon { color: var(--accent); font-weight: 700; }

        .wz-actions { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
        .wz-back { background: none; border: 1px solid var(--line); color: var(--muted); border-radius: 6px; padding: 13px 22px; font-size: 13px; cursor: pointer; }
        .wz-back:hover { border-color: var(--txt); color: var(--txt); }
        .pay-btn { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; background: var(--accent); color: #0a0b0e; border: 0; border-radius: 6px; padding: 15px 30px; font-weight: 700; font-size: 14px; cursor: pointer; font-family: var(--display); }
        .pay-btn [data-sm-grand] { font-family: var(--mono); }
        .pay-btn:hover { box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 18%, transparent); }

        .osum { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 28px; position: sticky; top: 96px; }
        .osum h2 { font-family: var(--archivo); font-weight: 800; font-size: 22px; margin-bottom: 20px; }
        .osum .oitem { display: grid; grid-template-columns: 50px 1fr auto; gap: 14px; align-items: center; margin-bottom: 16px; }
        .osum .oitem .img { height: 50px; width: 50px; background: var(--surface2); border-radius: 8px; overflow: hidden; position: relative; }
        .osum .oitem .img img { width: 100%; height: 100%; object-fit: cover; }
        .osum .oitem .qty-pill { position: absolute; top: -6px; right: -6px; background: var(--accent); color: #0a0b0e; width: 20px; height: 20px; border-radius: 5px; font-family: var(--mono); font-size: 11px; display: grid; place-items: center; font-weight: 700; }
        .osum .oitem .nm { font-size: 14px; font-weight: 600; } .osum .oitem .m { font-family: var(--mono); font-size: 11px; color: var(--muted); }
        .osum .oitem .pr { font-family: var(--mono); color: var(--accent); }
        .osum .divider { border: 0; border-top: 1px solid var(--line); margin: 16px 0; }
        .osum .r { display: flex; justify-content: space-between; font-size: 14px; margin: 11px 0; color: var(--muted); }
        .osum .r [data-sm-shipping] { font-family: var(--mono); }
        .osum .tot { display: flex; justify-content: space-between; font-size: 18px; font-weight: 700; border-top: 1px solid var(--line); padding-top: 15px; margin: 14px 0 18px; }
        .osum .tot [data-sm-grand] { font-family: var(--mono); color: var(--accent); }
        .osum .secure { font-family: var(--mono); font-size: 11px; color: var(--faint); text-align: center; }

        @media (max-width: 1000px) { .checkout { grid-template-columns: 1fr; } .osum { position: static; order: -1; } }
        @media (max-width: 540px) { .frow { grid-template-columns: 1fr; } }
    </style>

    @include('storefront.partials.number-anim')
    <noscript><style>.wz-step[hidden]{display:block!important}.wz-steps{display:none!important}.wz-back{display:none!important}</style></noscript>

    <main>
        <div class="wrap co-wrap">
            <h1>{{ __('site.checkout.title') }}</h1>

            @guest('customer')
                @if ($store->showsAccountUi())
                    <div class="co-signin-banner">
                        <span>{{ __('site.checkout.have_account') }}</span>
                        <span><a href="/account/login?intent=checkout">{{ __('site.common.sign_in') }}</a>@if ($store->allow_registration) · <a href="/account/register">{{ __('site.checkout.create_account_link') }}</a>@endif</span>
                    </div>
                @endif
            @endguest

            <form method="post" action="/checkout" data-wizard>
                @csrf
                @if ($errors->any())
                    <div class="errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <ol class="wz-steps" aria-hidden="true">
                    @foreach ($wizardSteps as $n => $label)
                        <li data-go-step="{{ $n }}" class="{{ $n === 1 ? 'is-current' : '' }}"><span class="dot">{{ $n }}</span> <span>{{ $label }}</span></li>
                    @endforeach
                </ol>

                <div class="checkout">
                    <div class="co-main">
                        <section class="wz-step is-current" data-step="1">
                            <div class="fset">
                                <h3><span class="num">1</span> {{ __('site.checkout.sec_contact') }}</h3>
                                @if ($customer)<div class="co-signed-in">{!! __('site.checkout.signed_in_as', ['email' => '<b>' . e($customer->email) . '</b>']) !!}</div>@endif
                                <div class="frow">
                                    <div class="field full"><label>{{ __('site.checkout.email') }}</label><input type="email" name="customer_email" value="{{ old('customer_email', $customer?->email) }}" placeholder="you@email.com" required></div>
                                    <div class="field full"><label>{{ __('site.checkout.full_name') }}</label><input type="text" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" required></div>
                                    <div class="field full"><label>{{ __('site.checkout.phone') }}</label><input type="tel" name="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}"></div>
                                </div>
                            </div>
                            <div class="fset">
                                <h3><span class="num">2</span> {{ __('site.checkout.sec_shipping') }}</h3>
                                <div class="frow">
                                    <div class="field full"><label>{{ __('site.checkout.street') }}</label><input type="text" name="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required></div>
                                    <div class="field"><label>{{ __('site.checkout.city') }}</label><input type="text" name="city" value="{{ old('city', $defaultAddress['city'] ?? '') }}" required></div>
                                    <div class="field"><label>{{ __('site.checkout.postal') }}</label><input type="text" name="postal_code" value="{{ old('postal_code', $defaultAddress['postal_code'] ?? '') }}" required></div>
                                    <div class="field"><label>{{ __('site.checkout.region') }}</label><input type="text" name="address_region" value="{{ old('address_region', $defaultAddress['region'] ?? '') }}"></div>
                                    <div class="field"><label>{{ __('site.checkout.country') }}</label><select name="country" required>@foreach ($countries as $code => $name)<option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $name }}</option>@endforeach</select></div>
                                </div>
                            </div>
                        </section>

                        <section class="wz-step" data-step="2" hidden>
                            <div class="fset"><h3><span class="num">3</span> {{ __('site.checkout.sec_shipping_method') }}</h3>@include('storefront.partials.shipping-methods')</div>
                            <div class="fset"><h3><span class="num">4</span> {{ __('site.checkout.sec_extras') }}</h3>@include('storefront.partials.checkout-extras')</div>
                        </section>

                        <section class="wz-step" data-step="3" hidden>
                            <div class="fset">
                                <h3><span class="num">5</span> {{ __('site.checkout.sec_payment') }}</h3>
                                @if ($isStripe)
                                    @include('storefront.partials.stripe-payment')
                                @else
                                    <div class="stub-notice"><span class="icon">!</span><div><strong>{{ __('site.checkout.stub_title') }}</strong> {!! __('site.checkout.stub_body', ['action' => '<em>' . $payLabel . '</em>']) !!}</div></div>
                                @endif
                            </div>
                        </section>

                        <div class="wz-actions">
                            <button type="button" class="wz-back" data-wz-prev hidden>← {{ __('site.checkout.wizard_back') }}</button>
                            <button type="submit" class="pay-btn" data-wz-primary data-pay-label="{{ $payLabel }}" data-continue-label="{{ __('site.checkout.wizard_continue') }}">
                                <span data-wz-label>{{ $payLabel }}</span><span data-wz-amount> · <span data-sm-grand>@money($grand)</span></span>
                            </button>
                        </div>
                    </div>

                    <aside class="osum">
                        <h2>{{ __('site.checkout.summary') }}</h2>
                        @foreach ($items as $row)
                            <div class="oitem">
                                <div class="img">@if ($row['product']->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($row['product']->image_path) }}" alt="">@endif<span class="qty-pill">{{ $row['quantity'] }}</span></div>
                                <div><div class="nm">{{ $row['product']->name }}</div>@if (! empty($row['variant']))<div class="m">{{ $row['variant']->label }}</div>@endif</div>
                                <div class="pr">@money($row['subtotal_cents'])</div>
                            </div>
                        @endforeach
                        <hr class="divider">
                        <div class="r"><span>{{ __('site.cart.subtotal') }}</span><span>@money($subtotal)</span></div>
                        <div class="r" data-sm-shipping-row><span>{{ __('site.cart.shipping') }}</span><span data-sm-shipping>@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span></div>
                        @if (! empty($discount) && $discountCents > 0)<div class="r"><span>{{ $discount->name }}</span><span>−@money($discountCents)</span></div>@endif
                        <div class="tot"><span>{{ __('site.cart.total') }}</span><span data-sm-grand>@money($grand)</span></div>
                        <div class="secure">// {{ __('site.checkout.secure_note') }}</div>
                    </aside>
                </div>
            </form>
        </div>
    </main>

    @include('storefront.partials.checkout-wizard')
@endsection
