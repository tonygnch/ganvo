@php
    $title = __('site.checkout.title');
    $subtotal = $total_cents;
    $shipping = $shipping_cents ?? ($subtotal >= 5000 ? 0 : 500);
    $discountCents = $discount_cents ?? 0;
    $grand = max(0, $subtotal + $shipping - $discountCents);
    $defaultAddress = $customer?->default_shipping_address ?? [];
@endphp
@extends('themes.menu.layout')

@section('content')
    <style>
        .checkout-page { max-width: 1100px; margin: 0 auto; padding: 4rem 1.5rem 6rem; }
        .checkout-eyebrow {
            font-size: 0.6875rem;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: center;
            margin: 0 0 .5rem;
        }
        .checkout-heading {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-style: italic;
            font-size: clamp(2rem, 4vw, 2.75rem);
            text-align: center;
            margin: 0 0 .5rem;
            color: var(--ink);
        }
        .back-link {
            display: block;
            text-align: center;
            margin: 0 0 2.5rem;
            font-size: 0.875rem;
            font-style: italic;
            color: var(--ink-soft);
            font-family: 'Playfair Display', Georgia, serif;
        }
        .back-link:hover { color: var(--ink); }

        .account-prompt {
            background: var(--paper-deep);
            padding: .875rem 1.25rem;
            margin: 0 0 2rem;
            font-size: 0.875rem;
            font-style: italic;
            font-family: 'Playfair Display', Georgia, serif;
            color: var(--ink);
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }
        .account-prompt a { color: var(--ink); border-bottom: 1px solid var(--ink); padding-bottom: 1px; }

        .errors {
            border: 1px solid var(--rule);
            background: var(--paper-deep);
            padding: .875rem 1rem;
            margin: 0 0 2rem;
            font-size: 0.875rem;
            color: var(--ink);
        }
        .errors-label { font-weight: 700; }
        .errors ul { margin: .25rem 0 0; padding-left: 1.125rem; }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 3rem;
            align-items: start;
        }
        .section { margin: 0 0 2rem; }
        .section-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--ink);
            margin: 0 0 1rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--rule);
            display: flex;
            align-items: baseline;
            gap: .625rem;
        }
        .section-title .step {
            font-size: 0.75rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
            font-style: normal;
            font-family: inherit;
            font-weight: 500;
        }

        .field { margin: 0 0 1rem; }
        .field-label {
            display: block;
            font-size: 0.6875rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--ink-soft);
            margin: 0 0 .5rem;
        }
        .field-input {
            width: 100%;
            background: var(--paper);
            border: 1px solid var(--rule);
            padding: .75rem 1rem;
            font-family: inherit;
            font-size: 0.9375rem;
            color: var(--ink);
            transition: border-color .2s ease;
        }
        .field-input:focus { outline: none; border-color: var(--ink); }
        .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }

        .signed-in {
            background: var(--paper-deep);
            padding: .625rem .875rem;
            margin: 0 0 1rem;
            font-size: 0.875rem;
            font-style: italic;
            font-family: 'Playfair Display', Georgia, serif;
            color: var(--ink);
        }

        .stub-notice {
            display: flex;
            gap: .875rem;
            padding: 1rem 1.25rem;
            background: var(--paper-deep);
            border-left: 3px solid var(--ink);
            font-size: 0.875rem;
            color: var(--ink-soft);
            font-style: italic;
            font-family: 'Playfair Display', Georgia, serif;
        }
        .stub-notice strong { display: block; color: var(--ink); margin: 0 0 .25rem; font-style: normal; font-family: inherit; font-weight: 700; }

        .summary {
            background: var(--paper-deep);
            border: 1px solid var(--rule);
            padding: 1.75rem;
            position: sticky;
            top: 2rem;
        }
        .summary h2 {
            margin: 0 0 1.5rem;
            font-family: 'Playfair Display', Georgia, serif;
            font-style: italic;
            font-size: 1.5rem;
            color: var(--ink);
            padding-bottom: .5rem;
            border-bottom: 1px solid var(--rule);
        }
        .line-items { margin: 0 0 1.5rem; }
        .line {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: .625rem;
            align-items: baseline;
            padding: .5rem 0;
            font-size: 0.875rem;
            color: var(--ink);
        }
        .line .qty-pill {
            min-width: 22px;
            text-align: center;
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 600;
            color: var(--ink-soft);
        }
        .line .name {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 0.9375rem;
        }
        .line .line-price { font-variant-numeric: tabular-nums; font-family: 'Playfair Display', Georgia, serif; font-weight: 600; }

        .summary-row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: baseline;
            padding: .5rem 0;
            color: var(--ink);
            font-size: 0.9375rem;
        }
        .summary-row .label { color: var(--ink-soft); font-style: italic; font-family: 'Playfair Display', Georgia, serif; }
        .summary-row .leader { border-bottom: 1px dotted var(--rule); margin: 0 .625rem .375rem; min-height: 1px; align-self: end; }
        .summary-row .num { font-variant-numeric: tabular-nums; }
        .summary-row.free .num { color: var(--primary-strong); font-weight: 600; }
        .summary-row.total {
            margin-top: .75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--rule);
        }
        .summary-row.total .label {
            font-style: normal;
            font-weight: 600;
            font-size: 0.6875rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--ink);
            font-family: inherit;
        }
        .summary-row.total .num {
            font-family: 'Playfair Display', Georgia, serif;
            font-weight: 700;
            font-size: 1.75rem;
        }

        .pay-btn {
            display: flex; align-items: center; justify-content: center; gap: .625rem;
            width: 100%;
            background: var(--ink);
            color: var(--paper);
            border: 0;
            padding: 1.125rem 1.5rem;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
            transition: background-color .2s ease;
        }
        .pay-btn:hover { background: var(--primary-strong); }
        .pay-btn .sep { opacity: .65; }
        .secure-line { text-align: center; margin-top: 1rem; font-size: 0.75rem; color: var(--ink-soft); font-style: italic; font-family: 'Playfair Display', Georgia, serif; }

        @media (max-width: 880px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="checkout-page">
        <p class="checkout-eyebrow">{{ __('site.checkout.summary') }}</p>
        <h1 class="checkout-heading">{{ __('site.checkout.title') }}</h1>
        <a href="/cart" class="back-link">← {{ __('site.checkout.back_to_cart') }}</a>

        @if (! $customer && $store->showsAccountUi())
            <div class="account-prompt">
                <span>{{ __('site.checkout.have_account') }} <a href="/account/login">{{ __('site.checkout.sign_in_link') }}</a> {{ __('site.checkout.for_faster') }}</span>
                @if ($store->allow_registration)<a href="/account/register">{{ __('site.checkout.create_account_link') }}</a>@endif
            </div>
        @endif

        <form method="post" action="/checkout">
            @csrf
            @if ($errors->any())
                <div class="errors">
                    <span class="errors-label">{{ __('site.common.error') ?? 'Error' }}</span>
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="checkout-grid">
                <div>
                    <section class="section">
                        <h2 class="section-title"><span class="step">i.</span>{{ __('site.checkout.sec_contact') }}</h2>
                        @if ($customer)
                            <div class="signed-in">{!! __('site.checkout.signed_in_as', ['email' => '<strong>' . e($customer->email) . '</strong>']) !!}</div>
                        @endif
                        <div class="field">
                            <label class="field-label" for="customer_email">{{ __('site.checkout.email') }}</label>
                            <input class="field-input" type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', $customer?->email) }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="customer_name">{{ __('site.checkout.full_name') }}</label>
                            <input class="field-input" type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', $customer?->name) }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="customer_phone">{{ __('site.checkout.phone') }} <small>({{ __('site.common.optional') }})</small></label>
                            <input class="field-input" type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $customer?->phone) }}">
                        </div>
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">ii.</span>{{ __('site.checkout.sec_shipping') }}</h2>
                        <div class="field">
                            <label class="field-label" for="address_line">{{ __('site.checkout.street') }}</label>
                            <input class="field-input" type="text" name="address_line" id="address_line" value="{{ old('address_line', $defaultAddress['line'] ?? '') }}" required>
                        </div>
                        <div class="field">
                            <label class="field-label" for="address_region">{{ __('site.checkout.region') }} <small>({{ __('site.common.optional') }})</small></label>
                            <input class="field-input" type="text" name="address_region" id="address_region" value="{{ old('address_region', $defaultAddress['region'] ?? '') }}">
                        </div>
                        <div class="fields-row">
                            <div class="field">
                                <label class="field-label" for="city">{{ __('site.checkout.city') }}</label>
                                <input class="field-input" type="text" name="city" id="city" value="{{ old('city', $defaultAddress['city'] ?? '') }}" required>
                            </div>
                            <div class="field">
                                <label class="field-label" for="postal_code">{{ __('site.checkout.postal') }}</label>
                                <input class="field-input" type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $defaultAddress['postal_code'] ?? '') }}" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="field-label" for="country">{{ __('site.checkout.country') }}</label>
                            <select class="field-input" name="country" id="country" required>
                                @php $selectedCountry = old('country', $defaultAddress['country'] ?? 'BG'); @endphp
                                @foreach ($countries as $code => $name)
                                    <option value="{{ $code }}" @selected($selectedCountry === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">iii.</span>{{ __('site.checkout.sec_shipping_method') }}</h2>
                        @include('storefront.partials.shipping-methods')
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">iv.</span>{{ __('site.checkout.sec_extras') }}</h2>
                        @include('storefront.partials.checkout-extras')
                    </section>

                    <section class="section">
                        <h2 class="section-title"><span class="step">v.</span>{{ __('site.checkout.sec_payment') }}</h2>
                        <div class="stub-notice">
                            <div>
                                <strong>{{ __('site.checkout.stub_title') }}</strong>
                                {!! __('site.checkout.stub_body', ['action' => '<em>' . __('site.checkout.action_place_order') . '</em>']) !!}
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="summary">
                    <h2>{{ __('site.checkout.summary') }}</h2>
                    <div class="line-items">
                        @foreach ($items as $row)
                            <div class="line">
                                <span class="qty-pill">{{ $row['quantity'] }}×</span>
                                <span class="name">{{ $row['product']->name }}@if (! empty($row['variant'])) <span class="line-variant">— {{ $row['variant']->label }}</span>@endif</span>
                                <span class="line-price">@money($row['subtotal_cents'])</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="summary-row">
                        <span class="label">{{ __('site.cart.subtotal') }}</span>
                        <span class="leader" aria-hidden="true"></span>
                        <span class="num">@money($subtotal)</span>
                    </div>
                    <div class="summary-row {{ $shipping === 0 ? 'free' : '' }}" data-sm-shipping-row>
                        <span class="label">{{ __('site.cart.shipping') }}</span>
                        <span class="leader" aria-hidden="true"></span>
                        <span class="num" data-sm-shipping>@if($shipping === 0){{ __('site.common.free') }}@else @money($shipping) @endif</span>
                    </div>
                    @if (! empty($discount) && $discountCents > 0)
                        <div class="summary-row discount">
                            <span class="label">{{ $discount->name }}</span>
                            <span class="leader" aria-hidden="true"></span>
                            <span class="num">−@money($discountCents)</span>
                        </div>
                    @endif
                    <div class="summary-row total">
                        <span class="label">{{ __('site.cart.total') }}</span>
                        <span class="leader" aria-hidden="true"></span>
                        <span class="num" data-sm-grand>@money($grand)</span>
                    </div>
                    <button type="submit" class="pay-btn">
                        <span>{{ __('site.checkout.pay_now') }}</span>
                        <span class="sep">·</span>
                        <span data-sm-grand>@money($grand)</span>
                    </button>
                    @if ($displayCurrency !== $baseCurrency)
                        <div class="secure-line">{{ __('site.checkout.charged_in', ['amount' => \App\Services\Money::format($grand, $baseCurrency)]) }}</div>
                    @endif
                    <div class="secure-line">{{ __('site.checkout.secure') }}</div>
                </aside>
            </div>
        </form>
    </div>
@endsection
